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
        Schema::table('cash_purchases', function (Blueprint $table) {
            // Add M&E System and Training fee columns for MicroBiz purchases
            $table->boolean('includes_me_system')->default(false)->after('delivery_fee');
            $table->decimal('me_system_fee', 10, 2)->default(0)->after('includes_me_system');
            $table->boolean('includes_training')->default(false)->after('me_system_fee');
            $table->decimal('training_fee', 10, 2)->default(0)->after('includes_training');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_purchases', function (Blueprint $table) {
            $table->dropColumn([
                'includes_me_system',
                'me_system_fee',
                'includes_training',
                'training_fee',
            ]);
        });
    }
};

