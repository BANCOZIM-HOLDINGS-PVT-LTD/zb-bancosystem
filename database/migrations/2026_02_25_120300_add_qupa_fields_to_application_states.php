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
            $table->foreignId('qupa_admin_id')->nullable()->after('agent_id')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_branch_id')->nullable()->after('qupa_admin_id')
                ->constrained('branches')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_states', function (Blueprint $table) {
            $table->dropForeign(['qupa_admin_id']);
            $table->dropForeign(['assigned_branch_id']);
            $table->dropColumn(['qupa_admin_id', 'assigned_branch_id']);
        });
    }
};
