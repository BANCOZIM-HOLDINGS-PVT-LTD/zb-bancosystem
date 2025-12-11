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
        Schema::table('delivery_trackings', function (Blueprint $table) {
            $table->string('post_office_tracking_number')->nullable()->after('courier_type');
            $table->string('post_office_vehicle_registration')->nullable()->after('post_office_tracking_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_trackings', function (Blueprint $table) {
            $table->dropColumn(['post_office_tracking_number', 'post_office_vehicle_registration']);
        });
    }
};
