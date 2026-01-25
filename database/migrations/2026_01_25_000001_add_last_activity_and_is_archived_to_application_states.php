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
            // Add last_activity for debounce/duplicate prevention
            $table->timestamp('last_activity')->nullable()->after('expires_at');

            // Add is_archived for "drop user" functionality after delivery
            $table->boolean('is_archived')->default(false)->after('last_activity');

            // Add index for archived status queries
            $table->index('is_archived');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_states', function (Blueprint $table) {
            $table->dropIndex(['is_archived']);
            $table->dropColumn(['last_activity', 'is_archived']);
        });
    }
};
