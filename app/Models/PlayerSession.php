<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayerSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_token',
        'current_room_id',
        'start_time',
        'end_time',
        'is_active',
        'has_completed'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean',
        'has_completed' => 'boolean'
    ];

    public function currentRoom()
    {
        return $this->belongsTo(Room::class, 'current_room_id');
    }

    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    }
}