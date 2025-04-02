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
        Schema::create('player_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_token')->unique();
            $table->foreignId('current_room_id')->constrained('rooms');
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('has_completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_sessions');
    }
};