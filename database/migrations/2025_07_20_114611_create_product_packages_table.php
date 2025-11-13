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
        Schema::create('product_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('package_type', ['standard', 'premium', 'economy', 'bundle', 'custom', 'promotional'])->default('standard');
            $table->decimal('base_price', 12, 2);
            $table->decimal('discounted_price', 12, 2)->nullable();
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->integer('minimum_quantity')->default(1);
            $table->integer('maximum_quantity')->nullable();
            $table->boolean('is_bundle')->default(false);
            $table->decimal('bundle_discount', 5, 2)->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->datetime('availability_start')->nullable();
            $table->datetime('availability_end')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->json('package_benefits')->nullable();
            $table->json('package_limitations')->nullable();
            $table->json('custom_pricing_rules')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'is_active']);
            $table->index(['package_type', 'is_active']);
            $table->index(['is_featured', 'is_active']);
            $table->index(['availability_start', 'availability_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_packages');
    }
};
