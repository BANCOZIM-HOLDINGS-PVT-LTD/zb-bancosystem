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
        // Check if personal_services table exists
        if (Schema::hasTable('personal_services')) {
            Schema::table('personal_services', function (Blueprint $table) {
                if (!Schema::hasColumn('personal_services', 'service_type')) {
                    $table->string('service_type')->nullable()->after('id');
                }
                
                if (!Schema::hasColumn('personal_services', 'application_state_id')) {
                    $table->foreignId('application_state_id')->nullable()->after('service_type')->constrained()->nullOnDelete();
                }
                
                if (!Schema::hasColumn('personal_services', 'reference_code')) {
                    $table->string('reference_code')->nullable()->after('application_state_id');
                    $table->index('reference_code');
                }
                
                if (!Schema::hasColumn('personal_services', 'redeemed_at')) {
                    $table->timestamp('redeemed_at')->nullable()->after('status');
                }
                
                if (!Schema::hasColumn('personal_services', 'redeemed_by')) {
                    $table->string('redeemed_by')->nullable()->after('redeemed_at');
                }
                
                if (!Schema::hasColumn('personal_services', 'redemption_notes')) {
                    $table->text('redemption_notes')->nullable()->after('redeemed_by');
                }
            });
            
            // Add index for service_type if not exists
            try {
                Schema::table('personal_services', function (Blueprint $table) {
                     // Check index existence raw query usually needed, or try/catch
                     $table->index('service_type');
                });
            } catch (\Exception $e) {
                // Index likely exists
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down needed as this is a fix migration
    }
};
