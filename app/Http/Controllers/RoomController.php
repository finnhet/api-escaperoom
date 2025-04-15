<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\Room;
use App\Models\GameObject;
use App\Models\PlayerSession;
use App\Models\Inventory;
use App\Services\RoomGeneratorService;

class RoomController extends Controller
{
    protected $roomGenerator;
    
    public function __construct(RoomGeneratorService $roomGenerator)
    {
        $this->roomGenerator = $roomGenerator;
    }

    public function startGame(Request $request)
    {
        // Get room count from request or default to 3-5
        $roomCount = $request->get('room_count', rand(3, 5));
        
        // Generate random rooms
        $rooms = $this->roomGenerator->generateRooms($roomCount);
        
        // Make sure we have generated at least one room
        if (empty($rooms)) {
            return response()->json(['error' => 'Failed to generate rooms'], 500);
        }
        
        $firstRoom = $rooms[0];
        
        // Create a player session with the first room's ID
        $sessionToken = md5(uniqid(rand(), true));
        
        $playerSession = PlayerSession::create([
            'session_token' => $sessionToken,
            'current_room_id' => $firstRoom->id,  // Use the actual first room ID instead of assuming it's 1
            'start_time' => now(),
            'is_active' => true
        ]);
        
        return response()->json([
            'message' => 'New game started with randomly generated rooms!',
            'session_token' => $sessionToken,
            'current_room' => $firstRoom->name,
            'description' => $firstRoom->description,
            'room_count' => count($rooms),
            'tip' => 'Use this token in your Authorization header for future requests',
        ]);
    }
    
    public function lookRoom(Request $request, $roomId)
    {
        $playerSession = $this->getPlayerSession($request);
        
        if (!$playerSession) {
            return response()->json(['error' => 'Invalid session. Please start a new game.'], 401);
        }
        
        // Modified to handle both ID and "room1", "room2", etc naming conventions
        $room = null;
        
        // First try to find the room by ID
        $room = Room::find($roomId);
        
        // If not found by ID, try finding by name "room{ID}"
        if (!$room) {
            $room = Room::where('name', 'room' . $roomId)->first();
        }
        
        if (!$room) {
            return response()->json(['error' => "Room {$roomId} not found."], 404);
        }
        
        if (!$this->canAccessRoom($playerSession, $room->id)) {
            return response()->json(['error' => 'You don\'t have access to this room yet.'], 403);
        }
        
        $objects = GameObject::where('room_id', $room->id)
            ->where('parent_id', null)
            ->where('is_visible', true)
            ->get();
        
        $objectList = $objects->map(function($object) {
            if ($object->type === 'door' && $object->is_locked) {
                return $object->name . ' (locked)';
            }
            return $object->name;
        });
        
        if ($playerSession->current_room_id != $room->id) {
            $playerSession->current_room_id = $room->id;
            $playerSession->save();
        }
        
        $hints = [];
        if ($room->is_final_room) {
            $hints[] = "This appears to be the final room. Find a way to escape!";
        }
        
        // Look for puzzles in the room
        $puzzles = GameObject::where('room_id', $room->id)
            ->whereNotNull('puzzle_type')
            ->where('is_visible', true)
            ->where('puzzle_solved', false)
            ->get();
            
        if ($puzzles->count() > 0) {
            $hints[] = "There might be puzzles to solve in this room. Examine objects carefully.";
        }
        
        return response()->json([
            'room' => $room->name,
            'description' => $room->description,
            'objects' => $objectList,
            'hints' => $hints
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
        
        $door = GameObject::where('room_id', $currentRoomId)
            ->where('name', 'door to room' . $nextRoomId)
            ->where('type', 'door')
            ->first();
        
        if ($door && $door->is_locked) {
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
                
                // Unlock the door since they have the key
                $door->is_locked = false;
                $door->save();
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
    
    public function finishGame(Request $request)
    {
        $playerSession = $this->getPlayerSession($request);
        
        if (!$playerSession) {
            return response()->json(['error' => 'Invalid session. Please start a new game.'], 401);
        }
        
        $currentRoom = Room::find($playerSession->current_room_id);
        
        if (!$currentRoom->is_final_room) {
            return response()->json(['error' => 'You need to reach the final room first!'], 403);
        }
        
        // Check if they've unlocked the exit door
        $exitDoor = GameObject::where('room_id', $currentRoom->id)
            ->where('name', 'exit door')
            ->where('type', 'door')
            ->first();
            
        if ($exitDoor && $exitDoor->is_locked) {
            return response()->json([
                'error' => 'The exit door is still locked! Find the golden key to unlock it.',
                'current_room' => $currentRoom->name
            ], 403);
        }
        
        // They've successfully completed the game
        $playerSession->end_time = now();
        $playerSession->has_completed = true;
        $playerSession->save();
        
        $duration = $playerSession->end_time->diffInMinutes($playerSession->start_time);
        
        return response()->json([
            'message' => "Congratulations! You've escaped the rooms!",
            'time_taken' => $duration . ' minutes',
            'rooms_explored' => Room::count()
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
        
        // Always allow access to room1 (first room)
        $room1 = Room::where('name', 'room1')->first();
        if ($room1 && $roomId == $room1->id) {
            return true;
        }
        
        $currentRoom = Room::find($playerSession->current_room_id);
        if (!$currentRoom) {
            return false;
        }
        
        $adjacentRooms = json_decode($currentRoom->adjacent_rooms, true) ?? [];
        
        return in_array($roomId, $adjacentRooms);
    }
}
