<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds the application_type column to application_states table
     * to explicitly flag the type of application: personal, microbiz, or sme.
     * This supports the SME Application Flow (Phase 1B).
     */
    public function up(): void
    {
        Schema::table('application_states', function (Blueprint $table) {
            $table->string('application_type', 20)->nullable()->after('payment_type')
                ->comment('Application type: personal, microbiz, or sme');
            $table->index('application_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_states', function (Blueprint $table) {
            $table->dropIndex(['application_type']);
            $table->dropColumn('application_type');
        });
    }
};
