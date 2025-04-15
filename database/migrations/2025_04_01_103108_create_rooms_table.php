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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->json('adjacent_rooms')->nullable(); // Store IDs of connected rooms
            $table->boolean('is_final_room')->default(false);
            $table->string('room_type')->default('standard'); // Types: standard, puzzle, boss, etc.
            $table->integer('difficulty')->default(1); // 1-5 scale
            $table->string('template_id')->nullable(); // For pre-designed room templates
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};