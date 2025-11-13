<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only run the MySQL-specific ALTER when using MySQL. Tests commonly use SQLITE
        // (in-memory) where ALTER ... MODIFY with ENUM will fail.
        if (DB::getDriverName() === 'mysql') {
            // Update the agents.type enum to include the application expected values.
            // Using raw statement because altering ENUM via Blueprint is not supported directly.
            DB::statement("ALTER TABLE `agents` MODIFY `type` ENUM('individual','corporate') NOT NULL DEFAULT 'individual'");
        } else {
            // For non-MySQL drivers (SQLite, etc.) we skip this alteration because
            // SQLite doesn't support ENUM or MODIFY. The column in those drivers will
            // typically be a string, which is acceptable for tests.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Revert to the previous enum values that existed prior to this migration.
            DB::statement("ALTER TABLE `agents` MODIFY `type` ENUM('field','online','direct') NOT NULL DEFAULT 'field'");
        } else {
            // no-op for non-mysql drivers
        }
    }
};
