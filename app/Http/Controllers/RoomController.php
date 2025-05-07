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
        
        $roomCount = $request->get('room_count', rand(3, 5));
        
        
        $rooms = $this->roomGenerator->generateRooms($roomCount);
        
        
        if (empty($rooms)) {
            return response()->json(['error' => 'Kamer genereren mislukt'], 500);
        }
        
        $firstRoom = $rooms[0];
        
        
        $sessionToken = md5(uniqid(rand(), true));
        
        $playerSession = PlayerSession::create([
            'session_token' => $sessionToken,
            'current_room_id' => $firstRoom->id,  
            'start_time' => now(),
            'is_active' => true
        ]);
        
        return response()->json([
            'message' => 'Nieuw game gestart',
            'session_token' => $sessionToken,
            'current_room' => $firstRoom->name,
            'description' => $firstRoom->description,
            'room_count' => count($rooms),
            'tip' => 'Hou deze token in de headers als je verder wilt spelen',
        ]);
    }
    
    public function lookRoom(Request $request, $roomId)
    {
        $playerSession = $this->getPlayerSession($request);
        
        if (!$playerSession) {
            return response()->json(['error' => 'Start een game api/start-game.'], 401);
        }
        
        $room = null;
        
        $room = Room::find($roomId);
        
        if (!$room) {
            $room = Room::where('name', 'room' . $roomId)->first();
        }
        
        if (!$room) {
            return response()->json(['error' => "kamer {$roomId} niet gevonden."], 404);
        }
        
        if (!$this->canAccessRoom($playerSession, $room->id)) {
            return response()->json(['error' => 'Je hebt nog geen toegang tot deze kamer.'], 403);
        }
        
        $objects = GameObject::where('room_id', $room->id)
            ->where('parent_id', null)
            ->where('is_visible', true)
            ->get();
        
        $objectList = $objects->map(function($object) {
            if ($object->type === 'door' && $object->name !== 'exit door') {
                if ($object->is_locked) {
                    return 'locked door';
                } else {
                    return 'door';
                }
            } else if ($object->type === 'door' && $object->is_locked) {
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
        
        
        $nextRoom = null;
        
        
        $nextRoom = Room::find($nextRoomId);
        
        
        if (!$nextRoom && is_numeric($nextRoomId)) {
            $nextRoom = Room::where('name', 'room' . $nextRoomId)->first();
        }
        
        if (!$nextRoom) {
            return response()->json(['error' => "Room {$nextRoomId} not found."], 404);
        }
        
        $adjacentRooms = json_decode($currentRoom->adjacent_rooms, true) ?? [];
        
        
        if (!in_array($nextRoom->id, $adjacentRooms)) {
            return response()->json(['error' => 'This room is not accessible from your current location.'], 400);
        }
        
        
        $door = GameObject::where('room_id', $currentRoomId)
            ->where(function($query) use ($nextRoom) {
                $query->where('name', 'door to room' . $nextRoom->id)
                    ->orWhere('name', 'like', '%door%' . $nextRoom->id . '%')
                    ->orWhere('name', 'like', '%door%' . str_replace('room', '', $nextRoom->name) . '%');
            })
            ->where('type', 'door')
            ->first();
        
        if ($door && $door->is_locked) {
            
            $keyName1 = "key{$nextRoom->id}";
            $keyName2 = "key" . str_replace('room', '', $nextRoom->name);
            
            $key = GameObject::where(function($query) use ($keyName1, $keyName2) {
                $query->where('name', $keyName1)
                      ->orWhere('name', $keyName2);
            })
            ->where('type', 'key')
            ->first();
            
            if ($key) {
                $hasKey = Inventory::where('player_session_id', $playerSession->id)
                    ->where('game_object_id', $key->id)
                    ->exists();
                    
                if (!$hasKey) {
                    return response()->json([
                        'error' => 'De Deur zit op slot. Zoek de juiste sleutel',
                        'current_room' => $currentRoom->name
                    ], 403);
                }
                
                $door->is_locked = false;
                $door->save();
            } else {
                
                $keys = Inventory::where('player_session_id', $playerSession->id)
                    ->whereHas('gameObject', function($query) {
                        $query->where('type', 'key');
                    })
                    ->with('gameObject')
                    ->get();
                
                $keyFound = false;
                $roomNumberStr = str_replace('room', '', $nextRoom->name);
                
                foreach ($keys as $inventoryItem) {
                    
                    if (strpos($inventoryItem->gameObject->name, $nextRoom->id) !== false ||
                        strpos($inventoryItem->gameObject->name, $roomNumberStr) !== false) {
                        $door->is_locked = false;
                        $door->save();
                        $keyFound = true;
                        break;
                    }
                }
                
                if (!$keyFound) {
                    return response()->json([
                        'error' => 'The door is locked. Find the right key!',
                        'current_room' => $currentRoom->name
                    ], 403);
                }
            }
        }
        
        $playerSession->current_room_id = $nextRoom->id;
        $playerSession->save();
        
        return response()->json([
            'message' => "You have opened {$nextRoom->name}!",
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
        
        $exitDoor = GameObject::where('room_id', $currentRoom->id)
            ->where('name', 'exit door')
            ->where('type', 'door')
            ->first();
            
        if ($exitDoor && $exitDoor->is_locked) {
            
            return response()->json([
                'error' => 'The exit door is still locked. You need to enter the 6-digit code from the torn papers.',
                'hint' => 'Find and collect all three torn paper pieces scattered throughout the rooms and combine their numbers to form the 6-digit code.'
            ], 403);
        }
        
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

    public function useCheatCode(Request $request)
    {
        $playerSession = $this->getPlayerSession($request);
        
        if (!$playerSession) {
            return response()->json(['error' => 'Invalid session. Please start a new game.'], 401);
        }

        $cheatCode = $request->get('code');

        if ($cheatCode == 'escape' || $cheatCode == 'letmeout' || $cheatCode == 'finishgame') {
            
            $finalRoom = Room::where('is_final_room', true)->first();
            
            if (!$finalRoom) {
                return response()->json(['error' => 'No final room found. Try starting a new game.'], 500);
            }
            
            $keys = GameObject::where('type', 'key')
                ->where('name', 'not like', '%golden key%')
                ->get();
            
            foreach ($keys as $key) {
                $hasKey = Inventory::where('player_session_id', $playerSession->id)
                    ->where('game_object_id', $key->id)
                    ->exists();
                    
                if (!$hasKey) {
                    Inventory::create([
                        'player_session_id' => $playerSession->id,
                        'game_object_id' => $key->id,
                        'acquired_at' => now()
                    ]);
                }
            }

            $doors = GameObject::where('type', 'door')->get();
            foreach ($doors as $door) {
                $door->is_locked = false;
                $door->save();
            }
            
            
            $playerSession->current_room_id = $finalRoom->id;
            $playerSession->save();

            return response()->json([
                'message' => 'CHEAT CODE ACTIVATED!',
                'details' => 'You have been teleported to the final room and all doors have been unlocked.',
                'next_steps' => 'Use the finish-game endpoint to complete the game now!',
                'current_room' => $finalRoom->name,
                'description' => $finalRoom->description
            ]);
        } else {
            return response()->json([
                'message' => 'Invalid cheat code. Try one of these: "escape", "letmeout", or "finishgame"'
            ], 400);
        }
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
        
        $room1 = Room::where('name', 'room1')->first();
        if ($room1 && $roomId == $room1->id) {
            return true;
        }
        
        $currentRoom = Room::find($playerSession->current_room_id);
        if (!$currentRoom) {
            return false;
        }
        
        $adjacentRooms = json_decode($currentRoom->adjacent_rooms, true) ?? [];

        if (in_array($roomId, $adjacentRooms)) {
            $door = GameObject::where('room_id', $currentRoom->id)
                ->where(function($query) use ($roomId) {
                    $query->where('name', 'door to room' . $roomId)
                        ->orWhere('name', 'like', '%door%' . $roomId . '%');
                })
                ->where('type', 'door')
                ->first();
            
            
            if ($door && $door->is_locked) {
                return false;
            }
            
            
            return true;
        }
        
        return false;
    }
}
