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
            $table->string('reference_code', 6)->nullable()->unique()->after('expires_at');
            $table->timestamp('reference_code_expires_at')->nullable()->after('reference_code');
            
            $table->index('reference_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_states', function (Blueprint $table) {
            $table->dropIndex(['reference_code']);
            $table->dropColumn(['reference_code', 'reference_code_expires_at']);
        });
    }
};
