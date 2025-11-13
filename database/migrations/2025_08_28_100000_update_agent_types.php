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
        // First modify the column to allow new values
        Schema::table('agents', function (Blueprint $table) {
            $table->enum('type', ['individual', 'corporate', 'field', 'online', 'direct'])->default('field')->change();
        });
        
        // Then update any existing 'individual' or 'corporate' types to 'field' as default
        DB::statement("UPDATE agents SET type = 'field' WHERE type = 'individual'");
        DB::statement("UPDATE agents SET type = 'field' WHERE type = 'corporate'");
        
        // Finally restrict to only the new enum values
        Schema::table('agents', function (Blueprint $table) {
            $table->enum('type', ['field', 'online', 'direct'])->default('field')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::table('agents')->where('type', 'field')->update(['type' => 'individual']);
        DB::table('agents')->where('type', 'online')->update(['type' => 'individual']);
        DB::table('agents')->where('type', 'direct')->update(['type' => 'individual']);
        
        Schema::table('agents', function (Blueprint $table) {
            $table->enum('type', ['individual', 'corporate'])->default('individual')->change();
        });
    }
};