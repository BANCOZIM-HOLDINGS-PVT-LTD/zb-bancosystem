<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // MySQL: Update the agents.type enum to include the application expected values.
            DB::statement("ALTER TABLE `agents` MODIFY `type` ENUM('individual','corporate') NOT NULL DEFAULT 'individual'");
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: Drop constraint, update values, add new constraint
            try {
                DB::statement('ALTER TABLE agents DROP CONSTRAINT IF EXISTS agents_type_check');
            } catch (\Exception $e) {
                // Constraint might not exist
            }

            // Update any values that won't be in new enum
            DB::statement("UPDATE agents SET type = 'individual' WHERE type NOT IN ('individual', 'corporate')");

            // Add new check constraint
            DB::statement("ALTER TABLE agents ADD CONSTRAINT agents_type_check CHECK (type IN ('individual', 'corporate'))");
            DB::statement("ALTER TABLE agents ALTER COLUMN type SET DEFAULT 'individual'");
        } else {
            // For non-MySQL/PostgreSQL drivers (SQLite, etc.) we skip this alteration
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // Revert to the previous enum values that existed prior to this migration.
            DB::statement("ALTER TABLE `agents` MODIFY `type` ENUM('field','online','direct') NOT NULL DEFAULT 'field'");
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: Drop constraint, update values, add old constraint
            try {
                DB::statement('ALTER TABLE agents DROP CONSTRAINT IF EXISTS agents_type_check');
            } catch (\Exception $e) {
                // Constraint might not exist
            }

            // Revert values
            DB::statement("UPDATE agents SET type = 'field' WHERE type IN ('individual', 'corporate')");

            // Add old constraint
            DB::statement("ALTER TABLE agents ADD CONSTRAINT agents_type_check CHECK (type IN ('field', 'online', 'direct'))");
            DB::statement("ALTER TABLE agents ALTER COLUMN type SET DEFAULT 'field'");
        } else {
            // no-op for non-mysql/postgresql drivers
        }
    }
};
