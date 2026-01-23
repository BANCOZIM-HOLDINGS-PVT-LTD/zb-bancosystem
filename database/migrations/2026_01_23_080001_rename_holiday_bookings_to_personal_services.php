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
        // Rename the table
        Schema::rename('holiday_bookings', 'personal_services');
        
        // Add new columns
        Schema::table('personal_services', function (Blueprint $table) {
            $table->string('service_type')->nullable()->after('id'); // vacation, school_fees, license, etc.
            $table->foreignId('application_state_id')->nullable()->after('service_type')->constrained()->nullOnDelete();
            $table->string('reference_code')->nullable()->after('application_state_id');
            $table->timestamp('redeemed_at')->nullable()->after('status');
            $table->string('redeemed_by')->nullable()->after('redeemed_at'); // Staff who processed redemption
            $table->text('redemption_notes')->nullable()->after('redeemed_by');
            
            // Update status to use new values
            // Current: pending, approved, rejected
            // New: pending, approved, redeemed, cancelled
            
            // Indexes
            $table->index('service_type');
            $table->index('reference_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_services', function (Blueprint $table) {
            $table->dropForeign(['application_state_id']);
            $table->dropColumn([
                'service_type',
                'application_state_id',
                'reference_code',
                'redeemed_at',
                'redeemed_by',
                'redemption_notes'
            ]);
        });
        
        Schema::rename('personal_services', 'holiday_bookings');
    }
};
