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
        Schema::create('loan_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('duration_months');
            $table->decimal('interest_rate', 8, 4);
            $table->enum('interest_type', ['simple', 'compound', 'flat', 'reducing', 'custom'])->default('reducing');
            $table->enum('calculation_method', ['standard', 'custom_formula', 'tiered', 'percentage_of_income'])->default('standard');
            $table->enum('payment_frequency', ['weekly', 'biweekly', 'monthly', 'quarterly', 'annually'])->default('monthly');
            $table->decimal('minimum_amount', 12, 2)->nullable();
            $table->decimal('maximum_amount', 12, 2)->nullable();
            $table->decimal('processing_fee', 10, 2)->default(0);
            $table->enum('processing_fee_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('insurance_rate', 8, 4)->default(0);
            $table->boolean('insurance_required')->default(false);
            $table->decimal('early_payment_penalty', 8, 4)->default(0);
            $table->decimal('late_payment_penalty', 8, 4)->default(0);
            $table->integer('grace_period_days')->default(0);
            $table->text('custom_formula')->nullable();
            $table->json('conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->datetime('effective_date')->nullable();
            $table->datetime('expiry_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'is_active']);
            $table->index(['is_active', 'is_default']);
            $table->index(['effective_date', 'expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_terms');
    }
};
