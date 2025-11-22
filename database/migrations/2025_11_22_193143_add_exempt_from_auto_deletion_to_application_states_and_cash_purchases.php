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
            $table->boolean('exempt_from_auto_deletion')->default(false)->after('metadata');
        });

        Schema::table('cash_purchases', function (Blueprint $table) {
            $table->boolean('exempt_from_auto_deletion')->default(false)->after('delivered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_states', function (Blueprint $table) {
            $table->dropColumn('exempt_from_auto_deletion');
        });

        Schema::table('cash_purchases', function (Blueprint $table) {
            $table->dropColumn('exempt_from_auto_deletion');
        });
    }
};
