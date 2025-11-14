<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = config('database.default');

        if ($driver === 'pgsql') {
            // PostgreSQL: Drop old constraint, modify column, add new constraint
            try {
                // Drop any existing check constraint
                DB::statement('ALTER TABLE agents DROP CONSTRAINT IF EXISTS agents_type_check');
            } catch (\Exception $e) {
                // Constraint might not exist, continue
            }

            // Update any existing values that won't be in new enum
            DB::statement("UPDATE agents SET type = 'field' WHERE type IN ('individual', 'corporate')");

            // Add new check constraint
            DB::statement("ALTER TABLE agents ADD CONSTRAINT agents_type_check CHECK (type IN ('field', 'online', 'direct'))");

            // Set default
            DB::statement("ALTER TABLE agents ALTER COLUMN type SET DEFAULT 'field'");
        } else {
            // MySQL/SQLite: Use Laravel's enum
            Schema::table('agents', function (Blueprint $table) {
                $table->enum('type', ['individual', 'corporate', 'field', 'online', 'direct'])->default('field')->change();
            });

            // Update existing values
            DB::statement("UPDATE agents SET type = 'field' WHERE type IN ('individual', 'corporate')");

            // Restrict to new values
            Schema::table('agents', function (Blueprint $table) {
                $table->enum('type', ['field', 'online', 'direct'])->default('field')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = config('database.default');

        if ($driver === 'pgsql') {
            // PostgreSQL: Drop constraint, update values, add old constraint
            try {
                DB::statement('ALTER TABLE agents DROP CONSTRAINT IF EXISTS agents_type_check');
            } catch (\Exception $e) {
                // Constraint might not exist
            }

            // Revert values
            DB::table('agents')->whereIn('type', ['field', 'online', 'direct'])->update(['type' => 'individual']);

            // Add old constraint
            DB::statement("ALTER TABLE agents ADD CONSTRAINT agents_type_check CHECK (type IN ('individual', 'corporate'))");
            DB::statement("ALTER TABLE agents ALTER COLUMN type SET DEFAULT 'individual'");
        } else {
            // MySQL/SQLite
            DB::table('agents')->whereIn('type', ['field', 'online', 'direct'])->update(['type' => 'individual']);

            Schema::table('agents', function (Blueprint $table) {
                $table->enum('type', ['individual', 'corporate'])->default('individual')->change();
            });
        }
    }
};
