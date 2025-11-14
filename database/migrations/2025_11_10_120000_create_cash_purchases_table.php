<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cash_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_number', 50)->unique(); // CP-XXXX-XXXX
            $table->enum('purchase_type', ['personal', 'microbiz']); // Purchase type

            // Product Information
            $table->unsignedBigInteger('product_id');
            $table->string('product_name');
            $table->decimal('cash_price', 10, 2);
            $table->decimal('loan_price', 10, 2)->nullable(); // For reference/savings calculation
            $table->string('category');

            // Customer Information
            $table->string('national_id', 50); // Format: XX-XXXXXXX-Y-ZZ
            $table->string('full_name');
            $table->string('phone', 50);
            $table->string('email')->nullable();

            // Delivery Information
            $table->enum('delivery_type', ['swift', 'gain_outlet']);
            $table->string('depot', 50); // Depot code or 'SWIFT'
            $table->string('depot_name')->nullable();
            $table->text('delivery_address')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->decimal('delivery_fee', 10, 2)->default(0);

            // Payment Information
            $table->string('payment_method', 50)->default('paynow');
            $table->decimal('amount_paid', 10, 2);
            $table->string('transaction_id')->nullable();
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');

            // Order Status
            $table->enum('status', ['pending', 'processing', 'dispatched', 'in_transit', 'delivered', 'failed', 'cancelled'])->default('pending');

            // Tracking Information
            $table->string('swift_tracking_number')->nullable(); // For Swift deliveries
            $table->json('status_history')->nullable(); // Track status changes

            // Timestamps
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for faster queries
            $table->index('purchase_number');
            $table->index('national_id');
            $table->index('phone');
            $table->index('payment_status');
            $table->index('status');
            $table->index(['purchase_type', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_purchases');
    }
};
