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
        Schema::table('payment_reminders', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_reminders', 'reminder_type')) {
                $table->string('reminder_type')->after('application_state_id')->default('deposit_pending');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_reminders', function (Blueprint $table) {
            if (Schema::hasColumn('payment_reminders', 'reminder_type')) {
                $table->dropColumn('reminder_type');
            }
        });
    }
};
