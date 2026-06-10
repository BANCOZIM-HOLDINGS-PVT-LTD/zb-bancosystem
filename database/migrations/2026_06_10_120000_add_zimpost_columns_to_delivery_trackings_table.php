<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('delivery_trackings', function (Blueprint $table) {
            $table->string('zimpost_delivery_id', 64)->nullable()->unique()->after('post_office_tracking_number');
            $table->string('zimpost_tracking_number', 64)->nullable()->after('zimpost_delivery_id');
            $table->timestamp('zimpost_last_synced_at')->nullable()->after('zimpost_tracking_number');
            $table->json('zimpost_snapshot')->nullable()->after('zimpost_last_synced_at');

            $table->index('zimpost_tracking_number');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_trackings', function (Blueprint $table) {
            $table->dropIndex(['zimpost_tracking_number']);
            $table->dropUnique(['zimpost_delivery_id']);
            $table->dropColumn([
                'zimpost_delivery_id',
                'zimpost_tracking_number',
                'zimpost_last_synced_at',
                'zimpost_snapshot',
            ]);
        });
    }
};
