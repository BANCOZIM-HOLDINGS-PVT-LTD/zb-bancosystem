<?php

namespace Tests\Feature;

use App\Enums\SSBLoanStatus;
use App\Events\PaymentReceived;
use App\Jobs\ImportSSBResponseJob;
use App\Models\AccountingTransaction;
use App\Models\ApplicationState;
use App\Models\BoosterBusiness;
use App\Models\BoosterCategory;
use App\Models\BoosterItem;
use App\Models\BoosterPackage;
use App\Models\BoosterTierItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductInventory;
use App\Models\ProductSubCategory;
use App\Services\CartService;
use App\Services\NotificationService;
use App\Services\SSBApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemainingWorkImplementationTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_ssb_response_job_polls_api_and_updates_application(): void
    {
        $application = ApplicationState::create([
            'session_id' => 'ssb-import-001',
            'channel' => 'web',
            'user_identifier' => 'client@example.com',
            'current_step' => SSBLoanStatus::AWAITING_SSB_APPROVAL->value,
            'reference_code' => 'SSBIMPORT001',
            'form_data' => [
                'employer' => 'government-ssb',
                'formResponses' => ['firstName' => 'Tapiwa', 'surname' => 'Moyo', 'mobile' => '+263771234567'],
            ],
            'metadata' => ['ssb_status' => SSBLoanStatus::AWAITING_SSB_APPROVAL->value],
        ]);

        $this->mock(SSBApiService::class, function ($mock) {
            $mock->shouldReceive('checkStatus')
                ->once()
                ->with('SSBIMPORT001')
                ->andReturn(['success' => true, 'status' => 'APPROVED', 'message' => 'Approved']);
        });

        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('sendStatusUpdateNotification')->atLeast()->once()->andReturn(true);
        });

        app(ImportSSBResponseJob::class)->handle(
            app(SSBApiService::class),
            app(\App\Services\SSBStatusService::class),
            app(NotificationService::class)
        );

        $application->refresh();

        $this->assertSame(SSBLoanStatus::APPROVED->value, $application->metadata['ssb_status']);
        $this->assertDatabaseHas('ssb_batch_logs', [
            'batch_type' => 'import',
            'status' => 'success',
            'success_count' => 1,
        ]);
    }

    public function test_payment_received_event_creates_accounting_transaction(): void
    {
        $application = ApplicationState::create([
            'session_id' => 'pay-001',
            'channel' => 'web',
            'user_identifier' => 'client@example.com',
            'current_step' => 'payment',
            'reference_code' => 'PAYAPP001',
            'form_data' => [],
        ]);

        $payment = Payment::create([
            'application_state_id' => $application->id,
            'provider' => 'paynow',
            'method' => 'ecocash',
            'amount' => 25.50,
            'currency' => 'USD',
            'status' => Payment::STATUS_PAID,
            'reference' => 'PAYAPP001',
            'receipt_number' => 'RCT-TEST',
            'paid_at' => now(),
        ]);

        event(new PaymentReceived($payment));

        $this->assertDatabaseHas('accounting_transactions', [
            'reference' => 'PAY-PAYAPP001',
            'type' => 'income',
            'source' => 'paynow',
            'amount' => 25.50,
        ]);
    }

    public function test_booster_frontend_catalog_returns_packages_with_included_items(): void
    {
        $category = BoosterCategory::create(['name' => 'Retail Shops', 'emoji' => 'R']);
        $business = BoosterBusiness::create([
            'booster_category_id' => $category->id,
            'name' => 'Hardware',
        ]);
        $item = BoosterItem::create([
            'booster_business_id' => $business->id,
            'item_code' => 'BST-001',
            'name' => 'Shelving',
            'unit_cost' => 100,
        ]);
        $package = BoosterPackage::create([
            'booster_business_id' => $business->id,
            'tier' => 'starter',
            'name' => 'Starter Hardware',
            'slug' => 'starter-hardware',
            'price' => 300,
            'loan_term' => 12,
        ]);
        BoosterTierItem::create([
            'booster_package_id' => $package->id,
            'booster_item_id' => $item->id,
            'quantity' => 3,
        ]);

        $response = $this->getJson('/api/boosters/frontend-catalog');

        $response->assertOk();
        $response->assertJsonPath('0.subcategories.0.businesses.0.scales.0.included_items.0.name', 'Shelving');
    }

    public function test_cart_service_totals_and_stock_validation_use_available_stock(): void
    {
        $category = ProductCategory::create(['name' => 'Building Materials', 'emoji' => 'B']);
        $subcategory = ProductSubCategory::create([
            'product_category_id' => $category->id,
            'name' => 'Cement',
        ]);
        $product = Product::create([
            'product_sub_category_id' => $subcategory->id,
            'name' => 'Cement Bag',
            'base_price' => 10,
        ]);
        ProductInventory::create([
            'product_id' => $product->id,
            'stock_quantity' => 5,
            'reserved_quantity' => 2,
        ]);

        $service = app(CartService::class);
        $this->assertTrue($service->addItem('cart-session', $product->id, 3)['success']);
        $this->assertSame(30.0, $service->getTotal('cart-session'));
        $this->assertTrue($service->validateStock('cart-session')['valid']);

        $this->assertFalse($service->addItem('cart-session', $product->id, 1)['success']);
    }
}
