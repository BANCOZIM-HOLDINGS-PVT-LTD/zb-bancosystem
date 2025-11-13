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
            // Drop the unique constraint first
            $table->dropUnique('application_states_reference_code_unique');
        });

        Schema::table('application_states', function (Blueprint $table) {
            // Change reference_code from VARCHAR(6) to VARCHAR(50) to accommodate National IDs
            $table->string('reference_code', 50)->nullable()->change();
        });

        Schema::table('application_states', function (Blueprint $table) {
            // Re-add the unique constraint
            $table->unique('reference_code', 'application_states_reference_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_states', function (Blueprint $table) {
            // Drop the unique constraint first
            $table->dropUnique('application_states_reference_code_unique');
        });

        Schema::table('application_states', function (Blueprint $table) {
            // Revert back to VARCHAR(6)
            $table->string('reference_code', 6)->nullable()->change();
        });

        Schema::table('application_states', function (Blueprint $table) {
            // Re-add the unique constraint
            $table->unique('reference_code', 'application_states_reference_code_unique');
        });
    }
};
