<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\Room;
use App\Models\GameObject;
use App\Models\PlayerSession;
use App\Models\Inventory;

class RoomController extends Controller
{
    public function startGame(Request $request)
    {
        $sessionToken = md5(uniqid(rand(), true));
        
        $playerSession = PlayerSession::create([
            'session_token' => $sessionToken,
            'current_room_id' => 1,
            'start_time' => now(),
        ]);
        
        return response()->json([
            'message' => 'New game started!',
            'session_token' => $sessionToken,
            'current_room' => 'room1',
            'tip' => 'Use this token in your Authorization header for future requests',
        ]);
    }
    
    public function lookRoom(Request $request, $roomId)
    {
        $playerSession = $this->getPlayerSession($request);
        
        if (!$playerSession) {
            return response()->json(['error' => 'Invalid session. Please start a new game.'], 401);
        }
        
        if (!$this->canAccessRoom($playerSession, $roomId)) {
            return response()->json(['error' => 'You don\'t have access to this room yet.'], 403);
        }
        
        $room = Room::find($roomId);
        
        if (!$room) {
            return response()->json(['error' => "Room {$roomId} not found."], 404);
        }
        
        $objects = GameObject::where('room_id', $roomId)
            ->where('parent_id', null)
            ->where('is_visible', true)
            ->get();
        
        $objectList = $objects->map(function($object) {
            if ($object->type === 'door' && $object->is_locked) {
                return $object->name . ' (locked)';
            }
            return $object->name;
        });
        
        if ($playerSession->current_room_id != $roomId) {
            $playerSession->current_room_id = $roomId;
            $playerSession->save();
        }
        
        return response()->json([
            'room' => $room->name,
            'description' => $room->description,
            'objects' => $objectList
        ]);
    }
    
    public function openRoom(Request $request, $nextRoomId)
    {
        $playerSession = $this->getPlayerSession($request);
        
        if (!$playerSession) {
            return response()->json(['error' => 'Invalid session. Please start a new game.'], 401);
        }
        
        $currentRoomId = $playerSession->current_room_id;
        
        $currentRoom = Room::find($currentRoomId);
        $adjacentRooms = json_decode($currentRoom->adjacent_rooms, true) ?? [];
        
        if (!in_array($nextRoomId, $adjacentRooms)) {
            return response()->json(['error' => 'This room is not accessible from your current location.'], 400);
        }
        
        $keyName = "key{$nextRoomId}";
        $key = GameObject::where('name', $keyName)->first();
        
        if ($key) {
            $hasKey = Inventory::where('player_session_id', $playerSession->id)
                ->where('game_object_id', $key->id)
                ->exists();
                
            if (!$hasKey) {
                return response()->json([
                    'error' => 'The door is locked. Find a key!',
                    'current_room' => $currentRoom->name
                ], 403);
            }
        }
        
        $playerSession->current_room_id = $nextRoomId;
        $playerSession->save();
        
        $nextRoom = Room::find($nextRoomId);
        
        return response()->json([
            'message' => "You have opened room {$nextRoomId}!",
            'current_room' => $nextRoom->name,
            'description' => $nextRoom->description
        ]);
    }
    
    private function getPlayerSession(Request $request)
    {
        $sessionToken = $request->header('Authorization');
        
        if (!$sessionToken) {
            return null;
        }
        
        return PlayerSession::where('session_token', $sessionToken)
            ->where('is_active', true)
            ->first();
    }
    
    private function canAccessRoom($playerSession, $roomId)
    {
        if ($playerSession->current_room_id == $roomId) {
            return true;
        }
        
        if ($roomId == 1) {
            return true;
        }
        
        $currentRoom = Room::find($playerSession->current_room_id);
        $adjacentRooms = json_decode($currentRoom->adjacent_rooms, true) ?? [];
        
        return in_array($roomId, $adjacentRooms);
    }
}
