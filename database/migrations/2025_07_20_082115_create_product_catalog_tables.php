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
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('emoji');
            $table->timestamps();
        });

        Schema::create('product_sub_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_sub_category_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->decimal('base_price', 10, 2);
            $table->string('image_url')->nullable();
            $table->timestamps();
        });

        Schema::create('product_package_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->decimal('multiplier', 10, 2);
            $table->decimal('custom_price', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('repayment_terms', function (Blueprint $table) {
            $table->id();
            $table->integer('months');
            $table->decimal('interest_rate', 5, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repayment_terms');
        Schema::dropIfExists('product_package_sizes');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_sub_categories');
        Schema::dropIfExists('product_categories');
    }
};