<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_reminders', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_reminders', 'channel')) {
                $table->string('channel')->default('sms')->after('reminder_stage');
            }

            if (!Schema::hasColumn('payment_reminders', 'delivery_status')) {
                $table->string('delivery_status')->default('sent')->after('channel');
            }

            if (!Schema::hasColumn('payment_reminders', 'metadata')) {
                $table->json('metadata')->nullable()->after('delivery_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_reminders', function (Blueprint $table) {
            foreach (['metadata', 'delivery_status', 'channel'] as $column) {
                if (Schema::hasColumn('payment_reminders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
