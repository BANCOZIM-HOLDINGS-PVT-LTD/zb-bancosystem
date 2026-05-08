<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('source');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('reference')->unique();
            $table->foreignId('application_state_id')->nullable()->constrained('application_states')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['type', 'source']);
            $table->index(['application_state_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_transactions');
    }
};
