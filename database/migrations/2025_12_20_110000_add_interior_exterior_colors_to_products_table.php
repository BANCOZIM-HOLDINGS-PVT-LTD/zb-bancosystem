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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'interior_colors')) {
                $table->json('interior_colors')->nullable()->after('colors');
            }
            if (!Schema::hasColumn('products', 'exterior_colors')) {
                $table->json('exterior_colors')->nullable()->after('interior_colors');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'interior_colors')) {
                $table->dropColumn('interior_colors');
            }
            if (Schema::hasColumn('products', 'exterior_colors')) {
                $table->dropColumn('exterior_colors');
            }
        });
    }
};
