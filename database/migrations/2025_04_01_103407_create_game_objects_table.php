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
        Schema::create('game_objects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->foreignId('room_id')->nullable()->constrained();
            $table->foreignId('parent_id')->nullable()->references('id')->on('game_objects');
            $table->string('type'); // door, container, item, key, etc.
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_takeable')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->boolean('has_hidden_items')->default(false);
            $table->boolean('revealed_hidden')->default(false);
            $table->boolean('is_taken')->default(false);
            
            // New fields for enhanced puzzles
            $table->string('puzzle_type')->nullable(); // logic, combination, pattern, etc.
            $table->text('puzzle_solution')->nullable(); // Store solutions for puzzles
            $table->text('puzzle_hint')->nullable(); // Hint for solving the puzzle
            $table->boolean('puzzle_solved')->default(false);
            $table->json('puzzle_state')->nullable(); // Current state of the puzzle
            $table->integer('puzzle_difficulty')->default(1); // 1-5 scale
            $table->json('template_data')->nullable(); // For object randomization
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_objects');
    }
};