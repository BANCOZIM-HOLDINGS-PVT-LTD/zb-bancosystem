<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes to application_states table
        Schema::table('application_states', function (Blueprint $table) {
            // Composite indexes for common query patterns
            $table->index(['user_identifier', 'channel'], 'idx_user_channel');
            $table->index(['current_step', 'created_at'], 'idx_step_created');
            $table->index(['channel', 'current_step'], 'idx_channel_step');
            $table->index(['expires_at', 'current_step'], 'idx_expires_step');
            $table->index(['reference_code_expires_at'], 'idx_ref_code_expires');

            // Individual indexes for frequent lookups
            $table->index(['created_at'], 'idx_created_at');
            $table->index(['updated_at'], 'idx_updated_at');

            // JSON field indexes (MySQL 5.7+ / PostgreSQL)
            // Skip JSON indexes for MariaDB compatibility
            if (config('database.default') === 'mysql' && !str_contains(DB::connection()->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION), 'MariaDB')) {
                // MySQL JSON indexes (not MariaDB)
                try {
                    DB::statement("ALTER TABLE application_states ADD INDEX idx_form_id ((JSON_EXTRACT(form_data, '$.formId')))");
                    DB::statement("ALTER TABLE application_states ADD INDEX idx_employer ((JSON_EXTRACT(form_data, '$.employer')))");
                    DB::statement("ALTER TABLE application_states ADD INDEX idx_has_account ((JSON_EXTRACT(form_data, '$.hasAccount')))");
                    DB::statement("ALTER TABLE application_states ADD INDEX idx_amount ((JSON_EXTRACT(form_data, '$.amount')))");
                    DB::statement("ALTER TABLE application_states ADD INDEX idx_email ((JSON_EXTRACT(form_data, '$.formResponses.emailAddress')))");
                    DB::statement("ALTER TABLE application_states ADD INDEX idx_mobile ((JSON_EXTRACT(form_data, '$.formResponses.mobile')))");
                    DB::statement("ALTER TABLE application_states ADD INDEX idx_national_id ((JSON_EXTRACT(form_data, '$.formResponses.nationalIdNumber')))");
                } catch (\Exception $e) {
                    // Skip JSON indexes if they fail
                }
            }
        });

        // Add indexes to state_transitions table
        Schema::table('state_transitions', function (Blueprint $table) {
            // Composite indexes for transition queries
            $table->index(['state_id', 'created_at'], 'idx_state_created');
            $table->index(['from_step', 'to_step'], 'idx_step_transition');
            $table->index(['channel', 'created_at'], 'idx_channel_created');
            $table->index(['to_step', 'created_at'], 'idx_to_step_created');

            // Individual indexes
            $table->index(['created_at'], 'idx_transition_created');
            // Skip JSON column index - PostgreSQL doesn't support btree on JSON
            // Use GIN index instead if needed: CREATE INDEX idx_transition_data ON state_transitions USING GIN (transition_data);
        });

        // Create GIN index for JSON column if using PostgreSQL
        // Use separate connection to avoid transaction issues
        if (config('database.default') === 'pgsql') {
            try {
                DB::statement('CREATE INDEX IF NOT EXISTS idx_transition_data ON state_transitions USING GIN (transition_data)');
            } catch (\Exception $e) {
                // Skip if index creation fails (column might not exist or already has index)
            }
        }

        // Create indexes for potential future tables
        // These are wrapped in their own checks internally
        $this->createDocumentIndexes();
        $this->createAuditIndexes();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_states', function (Blueprint $table) {
            $table->dropIndex('idx_user_channel');
            $table->dropIndex('idx_step_created');
            $table->dropIndex('idx_channel_step');
            $table->dropIndex('idx_expires_step');
            $table->dropIndex('idx_ref_code_expires');
            $table->dropIndex('idx_created_at');
            $table->dropIndex('idx_updated_at');

            if (config('database.default') === 'mysql' && !str_contains(DB::connection()->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION), 'MariaDB')) {
                try {
                    DB::statement("ALTER TABLE application_states DROP INDEX idx_form_id");
                    DB::statement("ALTER TABLE application_states DROP INDEX idx_employer");
                    DB::statement("ALTER TABLE application_states DROP INDEX idx_has_account");
                    DB::statement("ALTER TABLE application_states DROP INDEX idx_amount");
                    DB::statement("ALTER TABLE application_states DROP INDEX idx_email");
                    DB::statement("ALTER TABLE application_states DROP INDEX idx_mobile");
                    DB::statement("ALTER TABLE application_states DROP INDEX idx_national_id");
                } catch (\Exception $e) {
                    // Skip if indexes don't exist
                }
            }
        });

        Schema::table('state_transitions', function (Blueprint $table) {
            $table->dropIndex('idx_state_created');
            $table->dropIndex('idx_step_transition');
            $table->dropIndex('idx_channel_created');
            $table->dropIndex('idx_to_step_created');
            $table->dropIndex('idx_transition_created');
        });

        // Drop GIN index for PostgreSQL
        if (config('database.default') === 'pgsql') {
            try {
                DB::statement('DROP INDEX IF EXISTS idx_transition_data');
            } catch (\Exception $e) {
                // Skip if index doesn't exist
            }
        }
    }

    /**
     * Create indexes for documents table (if it exists)
     */
    private function createDocumentIndexes(): void
    {
        try {
            if (Schema::hasTable('documents')) {
                Schema::table('documents', function (Blueprint $table) {
                    $table->index(['application_state_id', 'document_type'], 'idx_app_doc_type');
                    $table->index(['document_type', 'created_at'], 'idx_doc_type_created');
                    $table->index(['file_path'], 'idx_file_path');
                    $table->index(['file_size'], 'idx_file_size');
                    $table->index(['mime_type'], 'idx_mime_type');
                    $table->index(['is_validated'], 'idx_is_validated');
                });
            }
        } catch (\Exception $e) {
            // Skip if table doesn't exist or indexes fail
        }
    }

    /**
     * Create indexes for audit table (if it exists)
     */
    private function createAuditIndexes(): void
    {
        try {
            if (Schema::hasTable('audit_logs')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->index(['auditable_type', 'auditable_id'], 'idx_auditable');
                    $table->index(['event', 'created_at'], 'idx_event_created');
                    $table->index(['user_id', 'created_at'], 'idx_user_created');
                    $table->index(['ip_address'], 'idx_ip_address');
                    $table->index(['user_agent'], 'idx_user_agent');
                });
            }
        } catch (\Exception $e) {
            // Skip if table doesn't exist or indexes fail
        }
    }
};
