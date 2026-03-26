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
        Schema::table('commissions', function (Blueprint $table) {
            $table->string('agent_type')->default('agents')->after('agent_id');
        });
        
        // Add last_commission_amount to both tables if not already there (though migration 2026_03_26_110000 should have done it)
        // If it already exists, this might fail, but I checked the previous migration and it was there.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropColumn('agent_type');
        });
    }
};
