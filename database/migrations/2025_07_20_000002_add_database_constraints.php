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
        $driver = Schema::connection(null)->getConnection()->getDriverName();

        // Add constraints to application_states table
        Schema::table('application_states', function (Blueprint $table) use ($driver) {
            // Add check constraints for enum values
            $driver = Schema::connection(null)->getConnection()->getDriverName();

            if ($driver === 'sqlite') {
                // SQLite does not support ADD CONSTRAINT for check constraints directly.
                // This will be handled in the table creation itself or requires a more complex workaround.
            } elseif ($driver === 'pgsql') {
                // PostgreSQL syntax - uses single quotes for strings and ~ for regex
                DB::statement("ALTER TABLE application_states ADD CONSTRAINT chk_application_states_channel CHECK (channel IN ('web', 'whatsapp', 'ussd', 'mobile_app'))");
                DB::statement('ALTER TABLE application_states ADD CONSTRAINT chk_application_states_current_step_length CHECK (LENGTH(current_step) <= 50)');
                DB::statement('ALTER TABLE application_states ADD CONSTRAINT chk_application_states_session_id_not_empty CHECK (LENGTH(session_id) > 0)');
                // PostgreSQL uses ~ for regex matching
                DB::statement("ALTER TABLE application_states ADD CONSTRAINT chk_application_states_reference_code_format CHECK (reference_code IS NULL OR (LENGTH(reference_code) >= 5 AND LENGTH(reference_code) <= 50 AND reference_code ~ '^[A-Z0-9]+$'))");
            } else {
                // MySQL syntax
                DB::statement('ALTER TABLE application_states ADD CONSTRAINT chk_application_states_channel CHECK (channel IN ("web", "whatsapp", "ussd", "mobile_app"))');
                DB::statement('ALTER TABLE application_states ADD CONSTRAINT chk_application_states_current_step_length CHECK (LENGTH(current_step) <= 50)');
                DB::statement('ALTER TABLE application_states ADD CONSTRAINT chk_application_states_session_id_not_empty CHECK (LENGTH(session_id) > 0)');
                DB::statement('ALTER TABLE application_states ADD CONSTRAINT chk_application_states_reference_code_format CHECK (reference_code IS NULL OR (LENGTH(reference_code) >= 5 AND LENGTH(reference_code) <= 50 AND reference_code REGEXP "^[A-Z0-9]+$"))');
            }
        });

        // Add foreign key constraint to state_transitions table
        Schema::table('state_transitions', function (Blueprint $table) use ($driver) {
            // The foreign key constraint should already exist from the original migration
            // But let's ensure it's properly set up with cascade options
            $table->dropForeign(['state_id']);
            $table->foreign('state_id')
                ->references('id')
                ->on('application_states')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Add check constraints
            if ($driver !== 'sqlite') {
                DB::statement('ALTER TABLE state_transitions ADD CONSTRAINT chk_state_transitions_to_step_length CHECK (LENGTH(to_step) <= 50)');
                DB::statement('ALTER TABLE state_transitions ADD CONSTRAINT chk_state_transitions_from_step_length CHECK (from_step IS NULL OR LENGTH(from_step) <= 50)');
                DB::statement('ALTER TABLE state_transitions ADD CONSTRAINT chk_state_transitions_channel_length CHECK (LENGTH(channel) <= 20)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_states', function (Blueprint $table) {
            $table->dropCheckConstraint('chk_application_states_channel');
            $table->dropCheckConstraint('chk_application_states_current_step_length');
            $table->dropCheckConstraint('chk_application_states_session_id_not_empty');
            $table->dropCheckConstraint('chk_application_states_reference_code_format');
        });

        Schema::table('state_transitions', function (Blueprint $table) {
            $table->dropCheckConstraint('chk_state_transitions_to_step_length');
            $table->dropCheckConstraint('chk_state_transitions_from_step_length');
            $table->dropCheckConstraint('chk_state_transitions_channel_length');

            // Restore original foreign key
            $table->dropForeign(['state_id']);
            $table->foreign('state_id')
                ->references('id')
                ->on('application_states')
                ->onDelete('cascade');
        });
    }
};
