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
        Schema::create('application_states', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->enum('channel', ['web', 'whatsapp', 'ussd', 'mobile_app']);
            $table->string('user_identifier');
            $table->string('current_step', 50);
            $table->json('form_data');
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_identifier', 'channel']);
            $table->index('session_id');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_states');
    }
};
