<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('microbiz_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->enum('tier', ['lite', 'standard', 'full_house', 'gold']);
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->unique(['product_id', 'tier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('microbiz_packages');
    }
};
