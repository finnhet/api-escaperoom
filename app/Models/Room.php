<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'adjacent_rooms',
        'is_final_room'
    ];

    protected $casts = [
        'adjacent_rooms' => 'array',
        'is_final_room' => 'boolean'
    ];

    public function objects()
    {
        return $this->hasMany(GameObject::class);
    }
}