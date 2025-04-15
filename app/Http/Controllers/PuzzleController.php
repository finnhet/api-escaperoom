<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GameObject;
use App\Models\PlayerSession;
use App\Models\Inventory;
use App\Models\Room;

class PuzzleController extends Controller
{
    public function solvePuzzle(Request $request, $roomId, $objectName)
    {
        $playerSession = $this->getPlayerSession($request);
        
        if (!$playerSession) {
            return response()->json(['error' => 'Invalid session. Please start a new game.'], 401);
        }
        
        if ($playerSession->current_room_id != $roomId) {
            return response()->json(['error' => 'You need to be in the room to interact with objects.'], 403);
        }
        
        $object = GameObject::where('room_id', $roomId)
            ->where('name', $objectName)
            ->where('is_visible', true)
            ->first();
        
        if (!$object) {
            return response()->json(['error' => "Object '{$objectName}' not found in this room."], 404);
        }
        
        if (!$object->puzzle_type) {
            return response()->json(['error' => "This object doesn't have a puzzle to solve."], 400);
        }
        
        if ($object->puzzle_solved) {
            return response()->json(['message' => "This puzzle has already been solved."], 200);
        }
        
        
        $solution = $request->get('solution');
        if (!$solution) {
            return response()->json(['error' => "Please provide a solution."], 400);
        }
        
        $result = $this->checkPuzzleSolution($object, $solution);
        
        if ($result['success']) {
            
            $object->puzzle_solved = true;
            $object->save();
            
            
            $this->handlePuzzleRewards($object);
            
            return response()->json([
                'message' => 'Puzzle solved successfully!',
                'result' => $result['message'],
                'reward' => $result['reward'] ?? null
            ]);
        } else {
            return response()->json([
                'message' => 'That solution is incorrect.',
                'hint' => $object->puzzle_hint
            ], 400);
        }
    }
    
    public function pullLever(Request $request, $roomId, $objectName)
    {
        $playerSession = $this->getPlayerSession($request);
        
        if (!$playerSession) {
            return response()->json(['error' => 'Invalid session. Please start a new game.'], 401);
        }
        
        if ($playerSession->current_room_id != $roomId) {
            return response()->json(['error' => 'You need to be in the room to interact with objects.'], 403);
        }
        
        $lever = GameObject::where('room_id', $roomId)
            ->where('name', $objectName)
            ->where('type', 'mechanism')
            ->where('is_visible', true)
            ->first();
        
        if (!$lever) {
            return response()->json(['error' => "Lever '{$objectName}' not found in this room."], 404);
        }
        
        
        $hiddenObjects = GameObject::where('room_id', $roomId)
            ->where('is_visible', false)
            ->get();
            
        $revealed = false;
        
        foreach ($hiddenObjects as $hiddenObject) {
            
            if (rand(1, 10) > 5) {
                $hiddenObject->is_visible = true;
                $hiddenObject->save();
                $revealed = true;
            }
        }
        
        if ($revealed) {
            return response()->json([
                'message' => 'You pulled the lever and revealed something hidden in the room!'
            ]);
        } else {
            return response()->json([
                'message' => 'You pulled the lever, but nothing seems to have happened.'
            ]);
        }
    }
    
    public function unlockWithKey(Request $request, $roomId, $objectName)
    {
        $playerSession = $this->getPlayerSession($request);
        
        if (!$playerSession) {
            return response()->json(['error' => 'Invalid session. Please start a new game.'], 401);
        }
        
        // Get the player's current room
        $currentRoomId = $playerSession->current_room_id;
        
        // Find the room the player is trying to interact with - support both IDs and names like "room3"
        $targetRoom = null;
        
        // First try direct ID lookup
        $targetRoom = Room::find($roomId);
        
        // If not found, try looking up by room name (if numeric ID was provided)
        if (!$targetRoom && is_numeric($roomId)) {
            $targetRoom = Room::where('name', 'room' . $roomId)->first();
        }
        
        if (!$targetRoom) {
            return response()->json(['error' => "Room {$roomId} not found."], 404);
        }
        
        // Check if the player is actually in the target room
        if ($playerSession->current_room_id != $targetRoom->id) {
            return response()->json(['error' => 'You need to be in the room to interact with objects.'], 403);
        }
        
        // Find the object to unlock
        $object = GameObject::where('room_id', $targetRoom->id)
            ->where('name', $objectName)
            ->where('is_visible', true)
            ->where('is_locked', true)
            ->first();
        
        if (!$object) {
            return response()->json(['error' => "Locked object '{$objectName}' not found in this room."], 404);
        }
        
        $keyName = $request->get('key');
        
        if (!$keyName) {
            return response()->json(['error' => "Please specify which key to use."], 400);
        }
        
        
        $keyObject = GameObject::where('name', $keyName)
            ->where('type', 'key')
            ->first();
            
        if (!$keyObject) {
            return response()->json(['error' => "Key '{$keyName}' does not exist."], 404);
        }
        
        $hasKey = Inventory::where('player_session_id', $playerSession->id)
            ->where('game_object_id', $keyObject->id)
            ->exists();
            
        if (!$hasKey) {
            return response()->json(['error' => "You don't have this key in your inventory."], 403);
        }
        
        
        $works = false;
        
        
        if ($object->type == 'door' && str_contains($object->name, 'door to room')) {
            $roomNumber = intval(str_replace('door to room', '', $object->name));
            if ($keyName == "key{$roomNumber}") {
                $works = true;
            }
        }
        
        else if ($object->name == 'exit door' && $keyName == 'golden key') {
            $works = true;
        }
        
        else if ($object->type == 'container') {
            $works = true;
        }
        
        if ($works) {
            $object->is_locked = false;
            $object->save();
            
            
            if ($object->has_hidden_items) {
                $hiddenItems = GameObject::where('parent_id', $object->id)
                    ->where('is_visible', false)
                    ->get();
                    
                foreach ($hiddenItems as $item) {
                    $item->is_visible = true;
                    $item->save();
                }
            }
            
            return response()->json([
                'message' => "You unlocked the {$object->name} with the {$keyName}!"
            ]);
        } else {
            return response()->json([
                'message' => "The {$keyName} doesn't fit the {$object->name}."
            ], 400);
        }
    }
    
    public function enterCombination(Request $request, $roomId, $objectName)
    {
        $playerSession = $this->getPlayerSession($request);
        
        if (!$playerSession) {
            return response()->json(['error' => 'Invalid session. Please start a new game.'], 401);
        }
        
        if ($playerSession->current_room_id != $roomId) {
            return response()->json(['error' => 'You need to be in the room to interact with objects.'], 403);
        }
        
        $object = GameObject::where('room_id', $roomId)
            ->where('name', $objectName)
            ->where('is_visible', true)
            ->where('is_locked', true)
            ->first();
        
        if (!$object) {
            return response()->json(['error' => "Object '{$objectName}' not found or is not locked."], 404);
        }
        
        $combination = $request->get('combination');
        
        if (!$combination) {
            return response()->json(['error' => "Please provide a combination."], 400);
        }
        
        
        if (!$object->puzzle_solution) {
            $solution = $this->generateRandomCombination();
            $object->puzzle_solution = $solution;
            $object->puzzle_hint = "The combination is {$this->getHintForCombination($solution)}";
            $object->save();
        }
        
        if ($combination == $object->puzzle_solution) {
            $object->is_locked = false;
            $object->puzzle_solved = true;
            $object->save();
            
            
            if ($object->has_hidden_items) {
                $hiddenItems = GameObject::where('parent_id', $object->id)
                    ->where('is_visible', false)
                    ->get();
                    
                foreach ($hiddenItems as $item) {
                    $item->is_visible = true;
                    $item->save();
                }
            }
            
            return response()->json([
                'message' => "The combination worked! The {$object->name} is now unlocked."
            ]);
        } else {
            
            return response()->json([
                'message' => "That combination didn't work.",
                'hint' => $object->puzzle_hint
            ], 400);
        }
    }
    
    private function generateRandomCombination()
    {
        
        $length = rand(3, 4);
        $combination = '';
        
        for ($i = 0; $i < $length; $i++) {
            $combination .= rand(0, 9);
        }
        
        return $combination;
    }
    
    private function getHintForCombination($combination)
    {
        
        $hints = [
            "a number with " . strlen($combination) . " digits",
            "related to the number of objects in this room",
            "hidden somewhere in plain sight",
            "the sum of all digits is " . array_sum(str_split($combination))
        ];
        
        return $hints[array_rand($hints)];
    }
    
    private function checkPuzzleSolution($object, $solution)
    {
        
        if (!$object->puzzle_solution) {
            if ($object->puzzle_type == 'combination') {
                $object->puzzle_solution = $this->generateRandomCombination();
            } else {
                
                $object->puzzle_solution = md5(uniqid());
            }
            $object->save();
        }
        
        
        if ($solution == $object->puzzle_solution) {
            $rewards = [
                "You found a hidden compartment!",
                "A secret passage has been revealed!",
                "You hear a click as something unlocks nearby.",
                "A panel slides away, revealing something hidden."
            ];
            
            return [
                'success' => true,
                'message' => "Puzzle solved successfully!",
                'reward' => $rewards[array_rand($rewards)]
            ];
        } else {
            return [
                'success' => false,
                'message' => "That solution is incorrect."
            ];
        }
    }
    
    private function handlePuzzleRewards($object)
    {
        
        if ($object->is_locked) {
            $object->is_locked = false;
            $object->save();
        }
        
        
        if ($object->has_hidden_items) {
            $hiddenItems = GameObject::where('parent_id', $object->id)
                ->where('is_visible', false)
                ->get();
                
            foreach ($hiddenItems as $item) {
                $item->is_visible = true;
                $item->save();
            }
        }
        
        
        if ($object->puzzle_type == 'trigger') {
            $hiddenObjects = GameObject::where('room_id', $object->room_id)
                ->where('is_visible', false)
                ->get();
                
            foreach ($hiddenObjects as $hiddenObject) {
                
                if (rand(0, 1)) {
                    $hiddenObject->is_visible = true;
                    $hiddenObject->save();
                }
            }
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
}