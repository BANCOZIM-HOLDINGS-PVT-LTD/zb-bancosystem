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
        Schema::table('application_states', function (Blueprint $table) {
            $table->decimal('deposit_amount', 10, 2)->nullable()->after('form_data');
            $table->boolean('deposit_paid')->default(false)->after('deposit_amount');
            $table->timestamp('deposit_paid_at')->nullable()->after('deposit_paid');
            $table->string('deposit_transaction_id')->nullable()->after('deposit_paid_at');
            $table->string('deposit_payment_method')->nullable()->after('deposit_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_states', function (Blueprint $table) {
            $table->dropColumn([
                'deposit_amount',
                'deposit_paid',
                'deposit_paid_at',
                'deposit_transaction_id',
                'deposit_payment_method'
            ]);
        });
    }
};
