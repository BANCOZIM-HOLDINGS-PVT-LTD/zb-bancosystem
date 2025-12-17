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
        Schema::create('cash_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_purchase_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('product_id')->nullable(); // Nullable in case product is deleted later vs keeping history
            $table->string('product_name');
            $table->string('category')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->json('metadata')->nullable(); // For things like size, color, scales
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_purchase_items');
    }
};
