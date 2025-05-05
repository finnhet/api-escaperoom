<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        
        Schema::table('game_objects', function (Blueprint $table) {
            
            if (Schema::hasColumn('game_objects', 'initial_room_id')) {
                $table->dropForeign(['initial_room_id']);
                $table->dropIndex(['initial_room_id']);
            }
            
            if (Schema::hasColumn('game_objects', 'current_room_id')) {
                $table->dropForeign(['current_room_id']);
                $table->dropIndex(['current_room_id']);
            }
            
            if (Schema::hasColumn('game_objects', 'room_id')) {
                $table->dropForeign(['room_id']);
                $table->dropIndex(['room_id']);
            }
        });
        
        
        if (Schema::hasTable('inventory')) {
            Schema::table('inventory', function (Blueprint $table) {
                if (Schema::hasColumn('inventory', 'player_session_id')) {
                    $table->dropForeign(['player_session_id']);
                    $table->dropIndex(['player_session_id']);
                }
            });
        }
    }

    
    public function down(): void
    {
        
    }
};
