<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = ['name', 'description'];

    public function objects()
    {
        return $this->hasMany(GameObject::class, 'room_id');
    }
}