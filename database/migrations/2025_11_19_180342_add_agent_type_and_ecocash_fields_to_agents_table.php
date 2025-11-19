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
        Schema::table('agents', function (Blueprint $table) {
            // Add agent_type column
            $table->enum('agent_type', ['online', 'physical'])->default('physical')->after('type');

            // Rename bank fields to ecocash fields
            $table->renameColumn('bank_name', 'ecocash_name');
            $table->renameColumn('bank_account', 'ecocash_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            // Drop agent_type column
            $table->dropColumn('agent_type');

            // Rename ecocash fields back to bank fields
            $table->renameColumn('ecocash_name', 'bank_name');
            $table->renameColumn('ecocash_number', 'bank_account');
        });
    }
};
