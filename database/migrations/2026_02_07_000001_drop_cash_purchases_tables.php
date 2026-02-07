<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration removes all cash purchase related tables as the system
     * is now a standalone credit facility.
     */
    public function up(): void
    {
        // Drop cash_purchase_items first (has foreign key to cash_purchases)
        Schema::dropIfExists('cash_purchase_items');
        
        // Drop cash_purchases table
        Schema::dropIfExists('cash_purchases');
    }

    /**
     * Reverse the migrations.
     * 
     * Note: This recreates the tables with minimal structure.
     * The original column structure cannot be fully restored.
     */
    public function down(): void
    {
        // Recreate cash_purchases table with basic structure
        Schema::create('cash_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_number')->unique();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone');
            $table->string('purchase_type');
            $table->decimal('cash_price', 12, 2);
            $table->decimal('loan_price', 12, 2)->nullable();
            $table->string('status')->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Recreate cash_purchase_items table
        Schema::create('cash_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_purchase_id')->constrained()->cascadeOnDelete();
            $table->string('product_name');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->timestamps();
        });
    }
};
