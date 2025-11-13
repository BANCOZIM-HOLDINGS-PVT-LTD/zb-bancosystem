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
            // Courier type selection (replaces/extends courier_service)
            $table->enum('courier_type', ['Swift', 'Gain Outlet', 'Bancozim', 'Bus Courier'])->nullable()->after('status');

            // Gain Outlet specific fields
            $table->string('gain_voucher_number')->nullable()->after('courier_service');
            $table->string('gain_depot_location')->nullable()->after('gain_voucher_number');

            // Bus Courier specific fields
            $table->string('bus_registration_number')->nullable()->after('gain_depot_location');
            $table->string('bus_driver_name')->nullable()->after('bus_registration_number');
            $table->string('bus_driver_phone')->nullable()->after('bus_driver_name');

            // Bancozim specific fields
            $table->string('bancozim_agent_name')->nullable()->after('bus_driver_phone');
            $table->string('bancozim_agent_phone')->nullable()->after('bancozim_agent_name');
            $table->string('bancozim_location')->nullable()->after('bancozim_agent_phone');

            // Client and delivery information
            $table->string('client_national_id')->nullable()->after('bancozim_location');
            $table->string('delivery_depot')->nullable()->after('client_national_id');

            // Delivery documentation (delivery_note for upload)
            $table->string('delivery_note')->nullable()->after('delivery_signature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_trackings', function (Blueprint $table) {
            $table->dropColumn([
                'courier_type',
                'gain_voucher_number',
                'gain_depot_location',
                'bus_registration_number',
                'bus_driver_name',
                'bus_driver_phone',
                'bancozim_agent_name',
                'bancozim_agent_phone',
                'bancozim_location',
                'client_national_id',
                'delivery_depot',
                'delivery_note',
            ]);
        });
    }
};
