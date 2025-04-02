<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameObject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'room_id',
        'parent_id',
        'type',
        'is_visible',
        'is_takeable',
        'is_locked',
        'has_hidden_items',
        'revealed_hidden',
        'is_taken'
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'is_takeable' => 'boolean',
        'is_locked' => 'boolean',
        'has_hidden_items' => 'boolean',
        'revealed_hidden' => 'boolean',
        'is_taken' => 'boolean'
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function parent()
    {
        return $this->belongsTo(GameObject::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(GameObject::class, 'parent_id');
    }
}