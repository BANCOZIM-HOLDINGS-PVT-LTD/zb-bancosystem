<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('application_state_id')->nullable()->constrained('application_states')->nullOnDelete();
            $table->string('cashier_reference')->unique();
            $table->decimal('received_amount', 12, 2);
            $table->string('receipt_number')->nullable()->unique();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['application_state_id', 'verified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_payments');
    }
};
