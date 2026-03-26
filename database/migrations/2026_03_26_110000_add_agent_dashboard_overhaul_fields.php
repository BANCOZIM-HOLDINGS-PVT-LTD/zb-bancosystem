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
        // Update agents table
        Schema::table('agents', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable();
            $table->boolean('is_deactivated')->default(false);
            $table->timestamp('deactivated_at')->nullable();
            $table->decimal('last_commission_amount', 15, 2)->default(0);
            $table->string('tier')->default('ordinary'); // ordinary, higher_achiever
        });

        // Update agent_applications table
        Schema::table('agent_applications', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable();
            $table->boolean('is_deactivated')->default(false);
            $table->timestamp('deactivated_at')->nullable();
            $table->decimal('last_commission_amount', 15, 2)->default(0);
            $table->string('tier')->default('ordinary'); // ordinary, higher_achiever
        });
        
        // Create agent_activity_logs table
        Schema::create('agent_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->string('agent_type'); // agents, agent_applications
            $table->string('activity_type'); // link_generation, commission_sent, reward_received, etc.
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Create agent_rewards table
        Schema::create('agent_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->string('agent_type');
            $table->string('reward_type'); // data_5gb, data_4gb, etc.
            $table->string('status')->default('pending'); // pending, sent
            $table->timestamp('sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        
        // Create agent_link_visits table to track real clicks
        Schema::create('agent_link_visits', function (Blueprint $table) {
            $table->id();
            $table->string('agent_code');
            $table->string('product_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_link_visits');
        Schema::dropIfExists('agent_rewards');
        Schema::dropIfExists('agent_activity_logs');
        
        Schema::table('agent_applications', function (Blueprint $table) {
            $table->dropColumn(['last_activity_at', 'is_deactivated', 'deactivated_at', 'last_commission_amount', 'tier']);
        });
        
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['last_activity_at', 'is_deactivated', 'deactivated_at', 'last_commission_amount', 'tier']);
        });
    }
};
