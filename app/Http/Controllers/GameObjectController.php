<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\GameObject;
use App\Models\PlayerSession;
use App\Models\Inventory;

class GameObjectController extends Controller
{
    public function lookObject(Request $request, $roomId, $objectName)
    {
        $playerSession = $this->getPlayerSession($request);
        
        if (!$playerSession) {
            return response()->json(['error' => 'Ongeldige sessie. Start een nieuw spel.'], 401);
        }
        
        if ($playerSession->current_room_id != $roomId) {
            return response()->json(['error' => 'Je moet in de kamer zijn om objecten te bekijken.'], 403);
        }
        
        $room = Room::find($roomId);
        
        if (!$room) {
            $room = Room::where('name', 'room' . $roomId)->first();
        }
        
        if (!$room) {
            return response()->json(['error' => "Kamer {$roomId} niet gevonden."], 404);
        }
        
        $object = GameObject::where('room_id', $room->id)
            ->where('name', $objectName)
            ->where('is_visible', true)
            ->first();
            
        if (!$object) {
            return response()->json(['error' => "Object '{$objectName}' niet gevonden in deze kamer."], 404);
        }
        
        
        $childObjects = GameObject::where('parent_id', $object->id)
            ->where('is_visible', true)
            ->get();
            
        $childObjectList = $childObjects->map(function($childObject) {
            return $childObject->name;
        });
        
        
        $description = $object->description;
        
        
        if ($object->type === 'door' && $object->is_locked) {
            $description .= " De deur is op slot.";
        } else if ($object->type === 'door' && !$object->is_locked) {
            $description .= " De deur is niet op slot.";
        }
        
        
        if ($object->type === 'container' && $object->is_locked) {
            $description .= " Het is vergrendeld.";
        }
        
        
        if ($object->puzzle_type && !$object->puzzle_solved) {
            $description .= " Er lijkt een puzzel mee verbonden te zijn.";
            
            
            if ($object->puzzle_hint) {
                $description .= " Hint: " . $object->puzzle_hint;
            }
        }
        
        
        $message = "Je bekijkt {$objectName}.";
        
        return response()->json([
            'message' => $message,
            'object' => [
                'naam' => $object->name,
                'beschrijving' => $description,
                'type' => $object->type,
                'is_vergrendeld' => $object->is_locked,
                'is_opneembaar' => $object->is_takeable,
            ],
            'bevat_items' => count($childObjectList) > 0 ? $childObjectList : null
        ]);
    }
    
    public function lookSubObject(Request $request, $roomId, $objectName, $subObjectName)
    {
        $playerSession = $this->getPlayerSession($request);
        
        if (!$playerSession) {
            return response()->json(['error' => 'Ongeldige sessie. Start een nieuw spel.'], 401);
        }
        
        if ($playerSession->current_room_id != $roomId) {
            return response()->json(['error' => 'Je moet in de kamer zijn om objecten te bekijken.'], 403);
        }
        
        $room = Room::find($roomId);
        
        if (!$room) {
            $room = Room::where('name', 'room' . $roomId)->first();
        }
        
        if (!$room) {
            return response()->json(['error' => "Kamer {$roomId} niet gevonden."], 404);
        }
        
        $parentObject = GameObject::where('room_id', $room->id)
            ->where('name', $objectName)
            ->where('is_visible', true)
            ->first();
            
        if (!$parentObject) {
            return response()->json(['error' => "Object '{$objectName}' niet gevonden in deze kamer."], 404);
        }
        
        
        if ($parentObject->is_locked) {
            return response()->json(['error' => "'{$objectName}' is vergrendeld. Je moet het eerst openen."], 403);
        }
        
        $subObject = GameObject::where('parent_id', $parentObject->id)
            ->where('name', $subObjectName)
            ->where('is_visible', true)
            ->first();
            
        if (!$subObject) {
            return response()->json(['error' => "Object '{$subObjectName}' niet gevonden in/op '{$objectName}'."], 404);
        }
        
        
        $childObjects = GameObject::where('parent_id', $subObject->id)
            ->where('is_visible', true)
            ->get();
            
        $childObjectList = $childObjects->map(function($childObject) {
            return $childObject->name;
        });
        
        
        $description = $subObject->description;
        
        
        if ($subObject->type === 'container' && $subObject->is_locked) {
            $description .= " Het is vergrendeld.";
        }
        
        
        $message = "Je bekijkt {$subObjectName} in/op {$objectName}.";
        
        return response()->json([
            'message' => $message,
            'object' => [
                'naam' => $subObject->name,
                'beschrijving' => $description,
                'type' => $subObject->type,
                'is_vergrendeld' => $subObject->is_locked,
                'is_opneembaar' => $subObject->is_takeable,
            ],
            'bevat_items' => count($childObjectList) > 0 ? $childObjectList : null
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
}
