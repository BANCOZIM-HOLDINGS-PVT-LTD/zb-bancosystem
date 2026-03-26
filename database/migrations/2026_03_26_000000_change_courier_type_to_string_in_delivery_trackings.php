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
        // Change courier_type from enum to string to allow more flexible values
        Schema::table('delivery_trackings', function (Blueprint $table) {
            $table->string('courier_type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_trackings', function (Blueprint $table) {
            $table->enum('courier_type', ['Swift', 'Gain Outlet', 'Bancozim', 'Bus Courier'])->nullable()->change();
        });
    }
};
