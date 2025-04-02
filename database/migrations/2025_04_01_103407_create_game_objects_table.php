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