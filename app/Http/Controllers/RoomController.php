<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\GameObject;
use App\Models\Inventory;

class RoomController extends Controller
{
    public function look($roomId, Request $request)
    {
        $room = Room::with('objects')->find($roomId);

        if (!$room) {
            return response()->json(['error' => "Room {$roomId} not found."], 404);
        }

        $keyName = "key" . ($roomId + 1);
        $keyAssigned = GameObject::where('room_id', $roomId)->where('is_hidden', true)->exists();
        if (!$keyAssigned) {
            $this->assignKeyToRandomObject($roomId);
        }

        return response()->json([
            'room' => $room->name,
            'objects' => $room->objects->pluck('name'),
        ]);
    }

    public function openDoor($roomId, Request $request)
    {
        $room = Room::find($roomId);

        if (!$room) {
            return response()->json(['error' => "Room {$roomId} not found."], 404);
        }

        $keyName = "key" . ($roomId + 1);
        $key = GameObject::where('name', $keyName)->first();

        if (!$key) {
            return response()->json(['error' => "Key {$keyName} not found."], 404);
        }

        $inventory = Inventory::where('user_id', auth()->id())->where('object_id', $key->id)->first();

        if (!$inventory) {
            return response()->json(['error' => "You don't have the key {$keyName}."], 403);
        }

        return response()->json(['message' => "Door to room {$roomId} opened."]);
    }
}