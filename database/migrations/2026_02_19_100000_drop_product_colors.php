<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['colors', 'interior_colors', 'exterior_colors']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('colors')->nullable()->after('image_url');
            $table->json('interior_colors')->nullable()->after('colors');
            $table->json('exterior_colors')->nullable()->after('interior_colors');
        });
    }
};
