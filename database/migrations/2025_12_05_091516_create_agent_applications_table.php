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
        Schema::create('agent_applications', function (Blueprint $table) {
            $table->id();
            $table->string('whatsapp_number')->index();
            $table->string('session_id')->index();
            
            // Personal Information
            $table->string('province');
            $table->string('first_name');
            $table->string('surname');
            $table->string('gender');
            $table->string('age_range');
            
            // Contact Information
            $table->string('voice_number');
            $table->string('whatsapp_contact');
            $table->string('ecocash_number');
            
            // ID Documents
            $table->string('id_front_url')->nullable();
            $table->string('id_back_url')->nullable();
            
            // Application Status
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->string('agent_code')->nullable()->unique(); // Generated after approval
            $table->string('referral_link')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_applications');
    }
};
