<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranty_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('warranty_enabled')->default(true);
            $table->text('warranty_text')->default('12 month warranty');
            $table->timestamps();
        });

        // Seed the single settings row
        DB::table('warranty_settings')->insert([
            'warranty_enabled' => true,
            'warranty_text'    => '12 month warranty',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_settings');
    }
};
