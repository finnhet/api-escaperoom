<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;
    
    protected $table = 'inventory';

    protected $fillable = [
        'player_session_id',
        'game_object_id',
        'acquired_at'
    ];

    protected $casts = [
        'acquired_at' => 'datetime'
    ];

    public function playerSession()
    {
        return $this->belongsTo(PlayerSession::class);
    }

    public function gameObject()
    {
        return $this->belongsTo(GameObject::class);
    }
}