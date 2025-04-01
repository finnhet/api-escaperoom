<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameObject extends Model
{
    protected $table = 'objects';
    protected $fillable = ['room_id', 'name', 'location', 'is_hidden'];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
}