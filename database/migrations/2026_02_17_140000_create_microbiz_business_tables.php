<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create microbiz_categories
        Schema::create('microbiz_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('emoji')->default('📦');
            $table->timestamps();
        });

        // 2. Create microbiz_subcategories
        Schema::create('microbiz_subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('microbiz_category_id')->constrained('microbiz_categories')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 3. Create microbiz_items
        Schema::create('microbiz_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('microbiz_subcategory_id')->constrained('microbiz_subcategories')->cascadeOnDelete();
            $table->string('item_code')->unique();
            $table->string('name');
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->string('unit')->nullable()->comment('e.g. bag, piece, litre');
            $table->timestamps();
        });

        // 4. Drop old microbiz tables and recreate
        Schema::dropIfExists('package_products');
        Schema::dropIfExists('microbiz_packages');

        // 5. Recreate microbiz_packages linked to subcategory
        Schema::create('microbiz_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('microbiz_subcategory_id')->constrained('microbiz_subcategories')->cascadeOnDelete();
            $table->string('tier');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->unique(['microbiz_subcategory_id', 'tier']);
        });

        // 6. Create microbiz_tier_items pivot
        Schema::create('microbiz_tier_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('microbiz_package_id')->constrained('microbiz_packages')->cascadeOnDelete();
            $table->foreignId('microbiz_item_id')->constrained('microbiz_items')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('microbiz_tier_items');
        Schema::dropIfExists('microbiz_packages');
        Schema::dropIfExists('microbiz_items');
        Schema::dropIfExists('microbiz_subcategories');
        Schema::dropIfExists('microbiz_categories');

        // Restore original microbiz_packages structure
        Schema::create('microbiz_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->enum('tier', ['lite', 'standard', 'full_house', 'gold']);
            $table->decimal('price', 10, 2);
            $table->timestamps();
            $table->unique(['product_id', 'tier']);
        });

        Schema::create('package_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('microbiz_package_id')->constrained('microbiz_packages')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->timestamps();
        });
    }
};
