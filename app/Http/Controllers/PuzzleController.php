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
        $room = Room::find($playerSession->current_room_id);
        $room_Id = $room->id;

        if (!$playerSession) {
            return response()->json(['error' => 'Ongeldige sessie. Start een nieuw spel.'], 401);
        }
        
        if ($playerSession->current_room_id != $room_Id) {
            return response()->json(['error' => 'Je moet in de kamer zijn om met objecten te kunnen interacteren.'], 403);
        }
        
        $object = GameObject::where('room_id', $room_Id)
            ->where('name', $objectName)
            ->where('is_visible', true)
            ->first();
        
        if (!$object) {
            return response()->json(['error' => "Object '{$objectName}' niet gevonden in deze kamer."], 404);
        }
        
        if (!$object->puzzle_type) {
            return response()->json(['error' => "Dit object heeft geen puzzel om op te lossen."], 400);
        }
        
        if ($object->puzzle_solved) {
            return response()->json(['message' => "Deze puzzel is al opgelost."], 200);
        }
        
        
        $solution = $request->get('solution');
        if (!$solution) {
            return response()->json(['error' => "Geef alstublieft een oplossing."], 400);
        }
        
        $result = $this->checkPuzzleSolution($object, $solution);
        
        if ($result['success']) {
            
            $object->puzzle_solved = true;
            $object->save();
            
            
            $this->handlePuzzleRewards($object);
            
            return response()->json([
                'message' => 'Puzzel succesvol opgelost!',
                'result' => $result['message'],
                'reward' => $result['reward'] ?? null
            ]);
        } else {
            return response()->json([
                'message' => 'Die oplossing is niet correct.',
                'hint' => $object->puzzle_hint
            ], 400);
        }
    }
    
    public function unlockWithKey(Request $request, $roomId, $objectName)
    {
        $playerSession = $this->getPlayerSession($request);
        
        if (!$playerSession) {
            return response()->json(['error' => 'Ongeldige sessie. Start een nieuw spel.'], 401);
        }
        
        
        $currentRoomId = $playerSession->current_room_id;
        
        
        $targetRoom = null;
        
        
        $targetRoom = Room::find($roomId);
        
        
        if (!$targetRoom && is_numeric($roomId)) {
            $targetRoom = Room::where('name', 'room' . $roomId)->first();
        }
        
        if (!$targetRoom) {
            return response()->json(['error' => "Kamer {$roomId} niet gevonden."], 404);
        }
        
        
        if ($playerSession->current_room_id != $targetRoom->id) {
            return response()->json(['error' => 'Je moet in de kamer zijn om met objecten te kunnen interacteren.'], 403);
        }
        
        
        $object = GameObject::where('room_id', $targetRoom->id)
            ->where('name', $objectName)
            ->where('is_visible', true)
            ->where('is_locked', true)
            ->first();
        
        if (!$object) {
            return response()->json(['error' => "Vergrendeld object '{$objectName}' niet gevonden in deze kamer."], 404);
        }
        
        $keyName = $request->get('key');
        
        if (!$keyName) {
            return response()->json(['error' => "Geef aan welke sleutel je wilt gebruiken."], 400);
        }
        
        
        $keyObject = GameObject::where('name', $keyName)
            ->where('type', 'key')
            ->first();
            
        if (!$keyObject) {
            return response()->json(['error' => "Sleutel '{$keyName}' bestaat niet."], 404);
        }
        
        $hasKey = Inventory::where('player_session_id', $playerSession->id)
            ->where('game_object_id', $keyObject->id)
            ->exists();
            
        if (!$hasKey) {
            return response()->json(['error' => "Je hebt deze sleutel niet in je inventaris."], 403);
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
                'message' => "Je hebt de {$object->name} ontgrendeld met de {$keyName}!"
            ]);
        } else {
            return response()->json([
                'message' => "De {$keyName} past niet op de {$object->name}."
            ], 400);
        }
    }
    
    public function enterCombination(Request $request, $roomId, $objectName)
    {
        $playerSession = $this->getPlayerSession($request);
        $room = Room::find($playerSession->current_room_id);
        $room_Id = $room->id;

        if (!$playerSession) {
            return response()->json(['error' => 'Ongeldige sessie. Start een nieuw spel.'], 401);
        }
        
        if ($playerSession->current_room_id != $room_Id) {
            return response()->json(['error' => 'Je moet in de kamer zijn om met objecten te kunnen interacteren.'], 403);
        }
        
        $object = GameObject::where('room_id', $room_Id)
            ->where('name', $objectName)
            ->where('is_visible', true)
            ->where('is_locked', true)
            ->first();
        
        if (!$object) {
            return response()->json(['error' => "Object '{$objectName}' niet gevonden of is niet vergrendeld."], 404);
        }
        
        $combination = $request->get('combination');
        
        if (!$combination) {
            return response()->json(['error' => "Geef alstublieft een combinatie op."], 400);
        }
        
        
        if (!$object->puzzle_solution) {
            $solution = $this->generateRandomCombination();
            $object->puzzle_solution = $solution;
            $object->puzzle_hint = "De combinatie is {$this->getHintForCombination($solution)}";
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
                'message' => "De combinatie werkte! De {$object->name} is nu ontgrendeld."
            ]);
        } else {
            
            return response()->json([
                'message' => "Die combinatie werkte niet.",
                'hint' => $object->puzzle_hint
            ], 400);
        }
    }
    
    public function repairKey(Request $request, $roomId, $objectName)
    {
        $playerSession = $this->getPlayerSession($request);
        $room = Room::find($playerSession->current_room_id);
        $room_Id = $room->id;

        if (!$playerSession) {
            return response()->json(['error' => 'Ongeldige sessie. Start een nieuw spel.'], 401);
        }
        
        if ($playerSession->current_room_id != $room_Id) {
            return response()->json(['error' => 'Je moet in de kamer zijn om met objecten te kunnen interacteren.'], 403);
        }
        
        
        $brokenKey = GameObject::where('room_id', $room_Id)
            ->where('name', $objectName)
            ->where('is_visible', true)
            ->where('puzzle_type', 'repair_key')
            ->first();
        
        if (!$brokenKey) {
            return response()->json(['error' => "Object '{$objectName}' niet gevonden of kan niet worden gerepareerd."], 404);
        }
        
        if ($brokenKey->puzzle_solved) {
            return response()->json(['message' => "Deze sleutel is al gerepareerd."], 200);
        }
        
        
        $glue = GameObject::where('name', 'glue')
            ->where('type', 'item')
            ->where('is_takeable', true)
            ->first();
        
        if (!$glue) {
            return response()->json(['error' => "Je hebt lijm nodig om deze sleutel te repareren."], 404);
        }
        
        $hasGlue = Inventory::where('player_session_id', $playerSession->id)
            ->whereHas('gameObject', function($query) {
                $query->where('name', 'glue');
            })
            ->exists();
        
        if (!$hasGlue) {
            return response()->json(['error' => "Je hebt geen lijm in je inventaris. Vind eerst wat lijm."], 400);
        }
        
        
        $targetRoomId = null;
        if ($brokenKey->puzzle_hint) {
            preg_match('/number\s+(\d+)/i', $brokenKey->puzzle_hint, $matches);
            if (!empty($matches)) {
                $targetRoomId = $matches[1];
            }
        }
        
        
        $keyName = $targetRoomId ? "key{$targetRoomId}" : "key%";
        $existingKey = GameObject::where('name', 'like', $keyName)
            ->where('type', 'key')
            ->where('is_visible', false) 
            ->first();
            
        if (!$existingKey) {
            
            $keyName = $targetRoomId ? "key{$targetRoomId}" : "repaired key";
            $existingKey = GameObject::create([
                'name' => $keyName,
                'description' => 'A repaired key that was previously broken.',
                'room_id' => $roomId,
                'type' => 'key',
                'is_visible' => true,
                'is_takeable' => true,
            ]);
        } else {
            
            $existingKey->is_visible = true;
            $existingKey->save();
        }
        
        
        Inventory::create([
            'player_session_id' => $playerSession->id,
            'game_object_id' => $existingKey->id,
            'acquired_at' => now()
        ]);
        
        
        $brokenKey->puzzle_solved = true;
        $brokenKey->save();
        
        return response()->json([
            'message' => "Je hebt de gebroken sleutel gerepareerd",
            'reward' => "Je hebt nu " . $existingKey->name . " in je inventory."
        ]);
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
            "een getal met " . strlen($combination) . " cijfers",
            "gerelateerd aan het aantal objecten in deze kamer",
            "ergens in het zicht verborgen",
            "de som van alle cijfers is " . array_sum(str_split($combination))
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
                "Je hebt een verborgen compartiment gevonden!",
                "Een geheime doorgang is onthuld!",
                "Je hoort een klik terwijl iets in de buurt ontgrendelt.",
                "Een paneel schuift weg en onthult iets verborgens."
            ];
            
            return [
                'success' => true,
                'message' => "Puzzel succesvol opgelost!",
                'reward' => $rewards[array_rand($rewards)]
            ];
        } else {
            return [
                'success' => false,
                'message' => "Die oplossing is niet correct."
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