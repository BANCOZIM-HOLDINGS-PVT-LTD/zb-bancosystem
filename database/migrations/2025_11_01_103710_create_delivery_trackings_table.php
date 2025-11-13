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
        Schema::create('delivery_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_state_id')->constrained('application_states')->onDelete('cascade');

            // Delivery Status
            $table->enum('status', ['pending', 'processing', 'dispatched', 'in_transit', 'out_for_delivery', 'delivered', 'failed', 'returned'])->default('pending');

            // Product Details
            $table->string('product_type')->nullable(); // phone, laptop, chicken, etc.
            $table->string('product_serial_number', 100)->nullable(); // For phones/laptops
            $table->string('outlet_voucher_number', 100)->nullable(); // For live broiler chickens

            // Delivery Details
            $table->string('swift_tracking_number', 100)->nullable();
            $table->string('courier_service', 100)->nullable(); // Swift, DHL, etc.
            $table->text('delivery_address')->nullable();
            $table->string('recipient_name', 255)->nullable();
            $table->string('recipient_phone', 50)->nullable();

            // Delivery Dates
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('estimated_delivery_date')->nullable();
            $table->timestamp('delivered_at')->nullable();

            // Notes and Updates
            $table->text('admin_notes')->nullable();
            $table->text('delivery_notes')->nullable(); // From courier
            $table->string('delivery_signature')->nullable(); // Path to signature image
            $table->string('delivery_photo')->nullable(); // Proof of delivery photo

            // Tracking
            $table->json('status_history')->nullable(); // Track all status changes
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // Admin user managing delivery

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('swift_tracking_number');
            $table->index('product_serial_number');
            $table->index('outlet_voucher_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_trackings');
    }
};
