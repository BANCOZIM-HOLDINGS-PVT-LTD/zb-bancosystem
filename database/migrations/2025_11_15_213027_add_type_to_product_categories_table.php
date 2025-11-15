<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->enum('type', ['hire_purchase', 'microbiz'])->default('hire_purchase')->after('emoji');
        });

        // Update existing categories based on their names
        // Hire Purchase categories (18 main categories)
        $hirePurchaseCategories = [
            'Cellphones',
            'Laptops and Printers',
            'ICT Accessories',
            'Kitchen ware',
            'Television and Entertainment',
            'Lounge ware',
            'Dining Room Sets',
            'Bedroom ware',
            'Solar systems',
            'Grooming Accessories',
            'Motor Sundries',
            'Motor cycles and Bicycle',
            'Building Materials',
            'Water storage and pumping systems',
            'Agric Mechanization',
            'Back to school',
            'Mother-to-be preparation',
            'Licensing & Certification Documents',
            'Leisure',
            // Legacy categories that should also be hire purchase
            'Electronics',
            'Home Appliances',
            'Furniture',
            'Solar Systems',
        ];

        DB::table('product_categories')
            ->whereIn('name', $hirePurchaseCategories)
            ->update(['type' => 'hire_purchase']);

        // All other categories are microbiz
        DB::table('product_categories')
            ->whereNotIn('name', $hirePurchaseCategories)
            ->update(['type' => 'microbiz']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
