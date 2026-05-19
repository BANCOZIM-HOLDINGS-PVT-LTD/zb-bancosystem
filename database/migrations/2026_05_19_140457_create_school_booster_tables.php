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
        Schema::create('school_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('emoji', 10)->nullable();
            $table->timestamps();
        });

        Schema::create('school_businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamps();
        });

        Schema::create('school_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_business_id')->constrained()->cascadeOnDelete();
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

        Schema::create('school_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_business_id')->constrained()->cascadeOnDelete();
            $table->enum('tier', ['essential', 'intermediate', 'advanced', 'premium']);
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('deposit', 12, 2)->default(0);
            $table->decimal('monthly_installment', 12, 2)->nullable();
            $table->integer('loan_term')->default(24);
            $table->decimal('interest_rate', 5, 2)->default(108.00);
            $table->timestamps();
        });

        Schema::create('school_tier_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_item_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->boolean('is_delivered')->default(false);
            $table->timestamps();
            $table->unique(['school_package_id', 'school_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_tier_items');
        Schema::dropIfExists('school_packages');
        Schema::dropIfExists('school_items');
        Schema::dropIfExists('school_businesses');
        Schema::dropIfExists('school_categories');
    }
};
