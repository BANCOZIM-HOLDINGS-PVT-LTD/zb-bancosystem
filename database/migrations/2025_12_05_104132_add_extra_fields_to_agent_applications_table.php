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
        Schema::table('agent_applications', function (Blueprint $table) {
            $table->string('id_number')->nullable()->after('surname');
            $table->string('application_number')->nullable()->unique()->after('id');
            $table->text('rejection_reason')->nullable()->after('status');
        });
        
        // Generate application numbers for existing records
        $applications = \App\Models\AgentApplication::whereNull('application_number')->get();
        foreach ($applications as $app) {
            $app->application_number = 'APP-' . str_pad($app->id, 6, '0', STR_PAD_LEFT);
            $app->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_applications', function (Blueprint $table) {
            $table->dropColumn(['id_number', 'application_number', 'rejection_reason']);
        });
    }
};
