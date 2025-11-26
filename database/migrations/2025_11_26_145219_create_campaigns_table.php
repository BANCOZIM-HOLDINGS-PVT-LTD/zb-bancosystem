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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('active'); // active, completed, cancelled
            $table->decimal('target_applications', 10, 2)->nullable();
            $table->decimal('target_sales', 10, 2)->nullable();
            $table->integer('target_conversions')->nullable();
            $table->json('performance_metrics')->nullable();
            $table->timestamps();
        });

        // Pivot table for many-to-many relationship between campaigns and agents
        Schema::create('agent_campaign', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->integer('applications_count')->default(0);
            $table->decimal('sales_total', 10, 2)->default(0);
            $table->integer('conversions_count')->default(0);
            $table->json('individual_metrics')->nullable();
            $table->timestamps();
            
            $table->unique(['agent_id', 'campaign_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_campaign');
        Schema::dropIfExists('campaigns');
    }
};
