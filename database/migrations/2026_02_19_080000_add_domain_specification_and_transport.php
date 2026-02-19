<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add domain to microbiz_categories to separate MicroBiz from Services
        Schema::table('microbiz_categories', function (Blueprint $table) {
            $table->string('domain')->default('microbiz')->after('emoji')
                ->comment('microbiz or service');
        });

        // 2. Add specification and pricing to microbiz_items
        Schema::table('microbiz_items', function (Blueprint $table) {
            $table->text('specification')->nullable()->after('name')
                ->comment('Brand, storage, RAM, size, tyre specs, etc.');
            $table->decimal('markup_percentage', 5, 2)->default(0)->after('unit_cost');
            $table->string('image_url')->nullable()->after('unit');
        });

        // 3. Add delivery flag to microbiz_tier_items
        Schema::table('microbiz_tier_items', function (Blueprint $table) {
            $table->boolean('is_delivered')->default(false)->after('quantity')
                ->comment('Whether this item needs delivery to client');
        });

        // 4. Add transport fields to microbiz_packages (tiers)
        Schema::table('microbiz_packages', function (Blueprint $table) {
            $table->string('transport_method')->nullable()->after('price')
                ->comment('small_truck ($20) or indrive ($5)');
            $table->string('courier')->default('zimpost')->after('transport_method');
            $table->string('ts_code')->nullable()->after('courier')
                ->comment('Transport from Source code, prefix TS-');
            $table->string('tc_code')->nullable()->after('ts_code')
                ->comment('Transport from Courier code, prefix TC-');
        });

        // 5. Add specification and transport to products (BancoZim)
        Schema::table('products', function (Blueprint $table) {
            $table->text('specification')->nullable()->after('name')
                ->comment('Brand, storage, RAM, size, tyre specs, etc.');
            $table->string('transport_method')->nullable()->after('markup_percentage')
                ->comment('small_truck ($20) or indrive ($5)');
            $table->string('ts_code')->nullable()->after('transport_method')
                ->comment('Transport from Source code, prefix TS-');
            $table->string('tc_code')->nullable()->after('ts_code')
                ->comment('Transport from Courier code, prefix TC-');
        });

        // 6. Add image_url to microbiz_subcategories for business images
        Schema::table('microbiz_subcategories', function (Blueprint $table) {
            $table->string('image_url')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('microbiz_categories', function (Blueprint $table) {
            $table->dropColumn('domain');
        });

        Schema::table('microbiz_items', function (Blueprint $table) {
            $table->dropColumn(['specification', 'markup_percentage', 'image_url']);
        });

        Schema::table('microbiz_tier_items', function (Blueprint $table) {
            $table->dropColumn('is_delivered');
        });

        Schema::table('microbiz_packages', function (Blueprint $table) {
            $table->dropColumn(['transport_method', 'courier', 'ts_code', 'tc_code']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['specification', 'transport_method', 'ts_code', 'tc_code']);
        });

        Schema::table('microbiz_subcategories', function (Blueprint $table) {
            $table->dropColumn('image_url');
        });
    }
};
