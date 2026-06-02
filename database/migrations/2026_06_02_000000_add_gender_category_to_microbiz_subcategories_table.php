<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('microbiz_subcategories', function (Blueprint $table) {
            // Audience tag for Personal Development → Vocational Short Courses.
            // 'fcc' = Female Centric, 'mcc' = Male Centric, null = audience-neutral.
            $table->string('gender_category')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('microbiz_subcategories', function (Blueprint $table) {
            $table->dropColumn('gender_category');
        });
    }
};
