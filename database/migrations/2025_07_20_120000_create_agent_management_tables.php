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
        // Agents table
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('agent_code')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('national_id')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->enum('type', ['individual', 'corporate'])->default('individual');
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('hire_date')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('bank_name')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(0.00); // Percentage
            $table->json('metadata')->nullable(); // Additional agent data
            $table->timestamps();
            
            $table->index(['status', 'type']);
            $table->index('agent_code');
        });

        // Teams table
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('team_leader_id')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->decimal('team_commission_rate', 5, 2)->default(0.00);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('team_leader_id')->references('id')->on('agents')->onDelete('set null');
            $table->index('status');
        });

        // Agent Team pivot table
        Schema::create('agent_teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('team_id');
            $table->date('joined_at');
            $table->date('left_at')->nullable();
            $table->enum('role', ['member', 'supervisor', 'leader'])->default('member');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->unique(['agent_id', 'team_id', 'is_active']);
            $table->index(['agent_id', 'is_active']);
            $table->index(['team_id', 'is_active']);
        });

        // Agent Performance tracking
        Schema::create('agent_performances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('applications_submitted')->default(0);
            $table->integer('applications_approved')->default(0);
            $table->integer('applications_rejected')->default(0);
            $table->decimal('total_loan_amount', 15, 2)->default(0.00);
            $table->decimal('commission_earned', 10, 2)->default(0.00);
            $table->decimal('conversion_rate', 5, 2)->default(0.00); // Percentage
            $table->json('metrics')->nullable(); // Additional performance metrics
            $table->timestamps();
            
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
            $table->unique(['agent_id', 'period_start', 'period_end']);
            $table->index(['agent_id', 'period_start']);
        });

        // Commissions table
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('application_id')->nullable();
            $table->string('reference_number')->unique();
            $table->enum('type', ['application', 'delivery', 'bonus', 'penalty'])->default('application');
            $table->decimal('amount', 10, 2);
            $table->decimal('rate', 5, 2); // Commission rate used
            $table->decimal('base_amount', 15, 2); // Amount commission was calculated on
            $table->enum('status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending');
            $table->date('earned_date');
            $table->date('paid_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
            $table->foreign('application_id')->references('id')->on('application_states')->onDelete('set null');
            $table->index(['agent_id', 'status']);
            $table->index(['status', 'earned_date']);
            $table->index('reference_number');
        });

        // Agent Referral Links
        Schema::create('agent_referral_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->string('code')->unique();
            $table->string('url');
            $table->string('campaign_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('click_count')->default(0);
            $table->integer('conversion_count')->default(0);
            $table->date('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
            $table->index(['agent_id', 'is_active']);
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_referral_links');
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('agent_performances');
        Schema::dropIfExists('agent_teams');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('agents');
    }
};
