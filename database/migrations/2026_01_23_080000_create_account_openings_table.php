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
        Schema::create('account_openings', function (Blueprint $table) {
            $table->id();
            $table->string('reference_code')->unique();
            $table->string('user_identifier'); // National ID or phone
            $table->json('form_data'); // Personal details, employer info
            $table->string('status')->default('pending'); // pending, account_opened, loan_eligible, rejected
            $table->string('zb_account_number')->nullable(); // Filled when account is opened
            $table->boolean('loan_eligible')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('loan_eligible_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('selected_product')->nullable(); // Store product for when they become eligible
            $table->foreignId('application_state_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('status');
            $table->index('user_identifier');
            $table->index('loan_eligible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_openings');
    }
};
