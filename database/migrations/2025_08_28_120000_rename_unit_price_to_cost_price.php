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
        // Check if products table exists and has unit_price column
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'unit_price')) {
            Schema::table('products', function (Blueprint $table) {
                // Rename unit_price to cost_price
                $table->renameColumn('unit_price', 'cost_price');
            });
        }
        
        // Also add a selling_price column if it doesn't exist
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'selling_price')) {
            Schema::table('products', function (Blueprint $table) {
                // Place after cost_price if it exists, otherwise after base_price
                if (Schema::hasColumn('products', 'cost_price')) {
                    $table->decimal('selling_price', 10, 2)->nullable()->after('cost_price');
                } else {
                    $table->decimal('selling_price', 10, 2)->nullable()->after('base_price');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'cost_price')) {
            Schema::table('products', function (Blueprint $table) {
                $table->renameColumn('cost_price', 'unit_price');
            });
        }
        
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'selling_price')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('selling_price');
            });
        }
    }
};