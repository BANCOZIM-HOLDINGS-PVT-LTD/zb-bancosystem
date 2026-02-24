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
        Schema::table('account_openings', function (Blueprint $table) {
            $table->timestamp('referred_at')->nullable()->after('loan_eligible_at');
            $table->string('referred_to_branch')->nullable()->after('referred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_openings', function (Blueprint $table) {
            $table->dropColumn(['referred_at', 'referred_to_branch']);
        });
    }
};
