<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'email_notifications_enabled')) {
                $table->boolean('email_notifications_enabled')->default(true)->after('whatsapp_number');
            }

            if (!Schema::hasColumn('users', 'email_preferences')) {
                $table->json('email_preferences')->nullable()->after('email_notifications_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'email_preferences')) {
                $table->dropColumn('email_preferences');
            }

            if (Schema::hasColumn('users', 'email_notifications_enabled')) {
                $table->dropColumn('email_notifications_enabled');
            }
        });
    }
};
