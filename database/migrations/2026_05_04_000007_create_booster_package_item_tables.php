<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booster_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booster_business_id')->constrained('booster_businesses')->cascadeOnDelete();
            $table->string('item_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('markup_percentage', 5, 2)->default(0);
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('booster_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booster_business_id')->constrained('booster_businesses')->cascadeOnDelete();
            $table->string('tier');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('deposit', 12, 2)->default(0);
            $table->decimal('monthly_installment', 12, 2)->default(0);
            $table->unsignedInteger('loan_term')->default(12);
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['booster_business_id', 'tier']);
        });

        Schema::create('booster_tier_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booster_package_id')->constrained('booster_packages')->cascadeOnDelete();
            $table->foreignId('booster_item_id')->constrained('booster_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->boolean('is_delivered')->default(true);
            $table->timestamps();

            $table->unique(['booster_package_id', 'booster_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booster_tier_items');
        Schema::dropIfExists('booster_packages');
        Schema::dropIfExists('booster_items');
    }
};
