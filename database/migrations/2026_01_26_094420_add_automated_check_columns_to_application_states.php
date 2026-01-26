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
            $table->string('check_type')->nullable()->comment('SSB or FCB');
            $table->string('check_status')->nullable()->comment('S=Success, F=Failure, B=Blacklisted, A=Approved, P=Pending');
            $table->json('check_result')->nullable()->comment('Full API response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_states', function (Blueprint $table) {
            //
        });
    }
};
