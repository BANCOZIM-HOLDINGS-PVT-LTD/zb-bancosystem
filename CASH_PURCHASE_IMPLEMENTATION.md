# Cash Purchase Implementation Plan

## Overview
This document outlines the implementation of the "Buy with Cash" feature for both Personal Products and MicroBiz Starter Pack.

## Completed Tasks

### ✅ 1. Welcome Page Updates
**File**: `resources/js/pages/welcome.tsx`

**Changes**:
- Added `ShoppingBag` and `DollarSign` icons
- Added two new intent options:
  - "Buy with Cash - Personal Products" (cashPurchasePersonal)
  - "Buy with Cash - MicroBiz Starter Pack" (cashPurchaseMicroBiz)
- Updated `handleIntentSelect` to route cash purchases to `cash.purchase` route with type parameter

**Routes**:
- Personal: `route('cash.purchase', { type: 'personal', language: 'en' })`
- MicroBiz: `route('cash.purchase', { type: 'microbiz', language: 'en' })`

---

## Pending Implementation

### 2. Cash Purchase Wizard Component
**File**: `resources/js/components/CashPurchase/CashPurchaseWizard.tsx`

**Steps**:
1. **Catalogue** - Product selection with cash prices
2. **Delivery** - Depot selection
3. **Summary** - Purchase summary
4. **Checkout** - ID, name, payment details
5. **Result** - Success/error with tracking info

**State Structure**:
```typescript
interface CashPurchaseData {
    purchaseType: 'personal' | 'microbiz';
    product: {
        id: number;
        name: string;
        cashPrice: number;
        loanPrice: number;
        category: string;
    };
    delivery: {
        type: 'swift' | 'gain_outlet';
        depot: string;
        address: string;
        city: string;
    };
    customer: {
        nationalId: string;
        fullName: string;
        phone: string;
        email?: string;
    };
    payment: {
        method: 'paynow';
        amount: number;
        transactionId?: string;
    };
}
```

### 3. Catalogue Component
**File**: `resources/js/components/CashPurchase/steps/Catalogue.tsx`

**Features**:
- Display products with **cash prices** (10-15% lower than loan prices)
- Category filtering
- Search functionality
- Product cards with images
- "Select Product" button

**Data Source**:
- Reuse existing product service: `resources/js/services/productService.ts`
- Add cash price calculation: `cashPrice = loanPrice * 0.85` (15% discount)

### 4. Delivery Depot Selection
**File**: `resources/js/components/CashPurchase/steps/DeliverySelection.tsx`

**Features**:
- Reuse existing delivery options from ApplicationWizard
- Swift (53 cities) or Gain Outlet (155+ depots)
- Address input for Swift
- Depot selection for Gain Outlet

**Reuse**: `resources/js/components/ApplicationWizard/steps/DeliverySelection.tsx`

### 5. Purchase Summary
**File**: `resources/js/components/CashPurchase/steps/PurchaseSummary.tsx`

**Display**:
- Selected product with cash price
- Delivery details
- Total amount (product + delivery fee if applicable)
- "Proceed to Checkout" button

### 6. Checkout Page
**File**: `resources/js/components/CashPurchase/steps/Checkout.tsx`

**Form Fields**:
- National ID (validated with Zimbabwean ID validator)
- Full Name
- Phone Number
- Email (optional)
- Payment Method: Paynow (default selected)

**Paynow Integration**:
- Display Paynow payment instructions
- QR code generation (optional)
- Manual Paynow number entry
- Transaction ID input after payment

**Submit Button**: "Complete Purchase"

### 7. Success/Error Pages
**Files**:
- `resources/js/components/CashPurchase/steps/Success.tsx`
- `resources/js/components/CashPurchase/steps/Error.tsx`

**Success Page**:
- ✅ "Purchase Successful!"
- Order confirmation number
- Customer details (ID, name)
- Product details
- Delivery details
- **Message**: "Please track your delivery status within 24 hours"
- Link to delivery tracking with auto-populated ID
- Print receipt button

**Error Page**:
- ❌ "Payment Failed" or error message
- Retry button
- Contact support info

---

## Backend Implementation

### 8. Database Migration
**File**: `database/migrations/YYYY_MM_DD_create_cash_purchases_table.php`

**Schema**:
```php
Schema::create('cash_purchases', function (Blueprint $table) {
    $table->id();
    $table->string('purchase_number')->unique(); // CP-XXXX-XXXX
    $table->string('purchase_type'); // 'personal' or 'microbiz'

    // Product details
    $table->unsignedBigInteger('product_id');
    $table->string('product_name');
    $table->decimal('cash_price', 10, 2);
    $table->string('category');

    // Customer details
    $table->string('national_id');
    $table->string('full_name');
    $table->string('phone');
    $table->string('email')->nullable();

    // Delivery details
    $table->string('delivery_type'); // 'swift' or 'gain_outlet'
    $table->string('depot');
    $table->text('delivery_address')->nullable();
    $table->string('city');

    // Payment details
    $table->string('payment_method'); // 'paynow'
    $table->decimal('amount_paid', 10, 2);
    $table->string('transaction_id')->nullable();
    $table->string('payment_status'); // 'pending', 'completed', 'failed'

    // Status
    $table->string('status'); // 'pending', 'processing', 'dispatched', 'delivered'
    $table->timestamp('paid_at')->nullable();
    $table->timestamp('dispatched_at')->nullable();
    $table->timestamp('delivered_at')->nullable();

    $table->timestamps();
    $table->softDeletes();

    $table->index('purchase_number');
    $table->index('national_id');
    $table->index('status');
    $table->index('payment_status');
});
```

### 9. Cash Purchase Model
**File**: `app/Models/CashPurchase.php`

```php
class CashPurchase extends Model
{
    protected $fillable = [
        'purchase_number', 'purchase_type', 'product_id', 'product_name',
        'cash_price', 'category', 'national_id', 'full_name', 'phone',
        'email', 'delivery_type', 'depot', 'delivery_address', 'city',
        'payment_method', 'amount_paid', 'transaction_id', 'payment_status',
        'status', 'paid_at', 'dispatched_at', 'delivered_at'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public static function generatePurchaseNumber()
    {
        return 'CP-' . strtoupper(Str::random(4)) . '-' . date('Ymd') . rand(100, 999);
    }
}
```

### 10. API Controller
**File**: `app/Http/Controllers/Api/CashPurchaseController.php`

**Methods**:
- `store()` - Create new cash purchase
- `show($purchaseNumber)` - Get purchase details
- `updatePayment()` - Update payment status
- `trackByNationalId($nationalId)` - Track purchases by ID

### 11. API Routes
**File**: `routes/api.php`

```php
// Cash Purchase routes
Route::prefix('cash-purchases')->group(function () {
    Route::post('/', [CashPurchaseController::class, 'store']);
    Route::get('/{purchaseNumber}', [CashPurchaseController::class, 'show']);
    Route::post('/{purchaseNumber}/payment', [CashPurchaseController::class, 'updatePayment']);
    Route::get('/track/{nationalId}', [CashPurchaseController::class, 'trackByNationalId']);
});
```

### 12. Web Routes
**File**: `routes/web.php`

```php
// Cash Purchase wizard
Route::get('/cash-purchase', [CashPurchaseController::class, 'index'])->name('cash.purchase');
Route::post('/cash-purchase/submit', [CashPurchaseController::class, 'submit'])->name('cash.purchase.submit');
Route::get('/cash-purchase/success/{purchaseNumber}', [CashPurchaseController::class, 'success'])->name('cash.purchase.success');
Route::get('/cash-purchase/track', [CashPurchaseController::class, 'track'])->name('cash.purchase.track');
```

---

## Admin Dashboard Integration

### 13. Filament Resource
**File**: `app/Filament/Resources/CashPurchaseResource.php`

**Features**:
- List all cash purchases
- Filter by status, payment status, date
- View purchase details
- Update status (processing → dispatched → delivered)
- Export to Excel/PDF
- Send notification to customer

**Table Columns**:
- Purchase Number
- Customer Name
- National ID
- Product
- Amount
- Payment Status
- Delivery Status
- Date

**Actions**:
- View details
- Mark as paid
- Mark as dispatched
- Mark as delivered
- Send tracking SMS

### 14. Admin Pages
**Files**:
- `app/Filament/Resources/CashPurchaseResource/Pages/ListCashPurchases.php`
- `app/Filament/Resources/CashPurchaseResource/Pages/ViewCashPurchase.php`

---

## Delivery Tracking Integration

### 15. Update Delivery Tracking
**File**: `resources/js/pages/DeliveryTracking.tsx`

**Changes**:
- Support tracking by National ID (for cash purchases)
- Add cash purchase support to existing tracking
- Display purchase type indicator

**API Update**:
- Modify delivery tracking API to include cash purchases
- Search by purchase number or national ID

---

## Paynow Integration

### 16. Paynow Service
**File**: `app/Services/PaynowService.php`

**Features**:
- Generate Paynow payment URL
- Verify payment status
- Handle webhook callbacks
- Return transaction details

**Configuration** (.env):
```env
PAYNOW_INTEGRATION_ID=your_integration_id
PAYNOW_INTEGRATION_KEY=your_integration_key
PAYNOW_RETURN_URL=https://yourdomain.com/cash-purchase/payment/callback
PAYNOW_RESULT_URL=https://yourdomain.com/api/paynow/webhook
```

### 17. Payment Flow
1. User submits checkout form
2. Backend creates pending cash purchase
3. Generate Paynow payment URL
4. Redirect user to Paynow
5. User completes payment
6. Paynow calls webhook
7. Update purchase payment_status to 'completed'
8. Redirect to success page

---

## Testing Checklist

- [ ] Welcome page shows 4 options (2 loan, 2 cash)
- [ ] Click "Buy with Cash - Personal Products" navigates to cash purchase wizard
- [ ] Click "Buy with Cash - MicroBiz" navigates to cash purchase wizard
- [ ] Catalogue displays products with cash prices
- [ ] Cash prices are 10-15% lower than loan prices
- [ ] Depot selection works
- [ ] Summary shows correct totals
- [ ] Checkout form validates National ID
- [ ] Paynow payment initiates
- [ ] Success page displays after payment
- [ ] Tracking message shows on success page
- [ ] Admin dashboard shows cash purchases
- [ ] Delivery tracking works with National ID
- [ ] SMS/Email notifications sent

---

## Implementation Priority

**Phase 1 - Core Flow** (Critical):
1. ✅ Welcome page updates
2. Cash Purchase Wizard component
3. Catalogue with cash pricing
4. Delivery selection
5. Purchase summary
6. Basic checkout (without Paynow)

**Phase 2 - Payment Integration**:
7. Checkout with Paynow
8. Payment verification
9. Success/Error pages

**Phase 3 - Backend & Database**:
10. Database migration
11. Model and controllers
12. API endpoints

**Phase 4 - Admin & Tracking**:
13. Admin dashboard integration
14. Delivery tracking integration
15. Notifications

---

## Next Steps

1. Create `CashPurchaseWizard.tsx` component
2. Create catalogue step
3. Create checkout step
4. Set up backend routes
5. Create database migration
6. Test complete flow

---

**Status**: Welcome page complete, wizard implementation in progress
**Estimated Time**: 4-6 hours for complete implementation
**Dependencies**: Existing product catalogue, delivery system, Paynow account