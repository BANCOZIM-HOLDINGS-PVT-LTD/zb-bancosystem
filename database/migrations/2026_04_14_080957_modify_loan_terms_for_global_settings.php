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
        Schema::table('loan_terms', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
        });

        // Purge old noise records
        \Illuminate\Support\Facades\DB::table('loan_terms')->truncate();

        // Seed the new global source of truth.
        // The user asked for a default monthly rate of 7% -> 84% annual.
        // processing fee -> admin fee = 6%.
        \App\Models\LoanTerm::create([
            'name' => 'Global Application Loan Term',
            'description' => 'Unified global setting for loan calculation (7% monthly -> 84% annual, 6% admin fee)',
            'interest_rate' => 84.00,
            'interest_type' => 'reducing',
            'processing_fee' => 6.00,
            'processing_fee_type' => 'percentage',
            'calculation_method' => 'standard',
            'payment_frequency' => 'monthly',
            'minimum_amount' => 0,
            'maximum_amount' => 10000000,
            'duration_months' => 12,
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_terms', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });
    }
};
