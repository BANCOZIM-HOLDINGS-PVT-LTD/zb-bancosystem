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
            $table->softDeletes();

            // Add additional indexes for better performance
            $table->index('deleted_at');
            $table->index(['current_step', 'deleted_at']);
            $table->index(['channel', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_states', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['deleted_at']);
            $table->dropIndex(['current_step', 'deleted_at']);
            $table->dropIndex(['channel', 'deleted_at']);
        });
    }
};
