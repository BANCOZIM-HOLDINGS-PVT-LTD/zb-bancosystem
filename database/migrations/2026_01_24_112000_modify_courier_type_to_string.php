<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('delivery_trackings')) {
             // Change courier_type to string to allow any value
             // Use raw statement for Enum change as Doctrine DBAL can struggle with Enums sometimes
             DB::statement("ALTER TABLE `delivery_trackings` MODIFY COLUMN `courier_type` VARCHAR(100) NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to Enum if needed (might lose data if values don't matched)
        // Leaving as string is safer for rollback
    }
};
