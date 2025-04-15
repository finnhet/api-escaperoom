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
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_session_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('game_object_id');
            $table->timestamp('acquired_at');
            $table->timestamps();
            
            // This will be added after game_objects table is created
            // We'll add the foreign key later in a separate method
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};