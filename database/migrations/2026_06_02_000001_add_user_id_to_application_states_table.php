<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_states', function (Blueprint $table) {
            // Links an in-progress application to a registered client so progress
            // can be resumed across devices/browsers, not just the original browser.
            $table->unsignedBigInteger('user_id')->nullable()->after('user_identifier');
            $table->index('user_id');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('application_states', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
