<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\GameObject;
use App\Models\PlayerSession;
use App\Models\Inventory;

class GameObjectController extends Controller
{
    public function lookObject(Request  $request, $roomId, $objectName)
    {
        $playerSession = $this->getPlayerSession($request);
        $room = Room::find($playerSession->current_room_id);
        $room_Id = $room->id;

        if (!$playerSession) {
            return response()->json(['error' => 'Start een game api/start-game.'], 401);
        }

        if ($playerSession->current_room_id != $room_Id) {
            return response()->json(['error' => 'Je moet in de kamer zijn om dit object te bekijken.'], 403);
        }
 
        $object = GameObject::where('room_id', $room_Id)
            ->where('name', $objectName)
            ->where('is_visible', true)
            ->first();
        
        if (!$object) {
            return response()->json(['error' => "Object '{$objectName}' niet gevonden in deze kamer "], 404);
        }

        if ($object->is_locked) {
            return response()->json(['error' => 'Dit object is vergrendeld.'], 403);
        }
        
        $childObjects = GameObject::where('parent_id', $object->id)
            ->where('is_visible', true)
            ->get();
        
        $response = [
            'location' => $object->name,
            'description' => $object->description
        ];
        
        if ($childObjects->count() > 0) {
            $response['objects'] = $childObjects->pluck('name');
        } else if ($object->type === 'container') {
            $response['objects'] = [];
            $response['message'] = 'Deze ' . $object->name . ' lijkt leeg te zijn.';
        }
        
        return response()->json($response);
    }
    

    public function lookSubObject(Request $request, $roomId, $objectName, $subObjectName)
    {
        $playerSession = $this->getPlayerSession($request);
        $room = Room::find($playerSession->current_room_id);
        $room_Id = $room->id;
        
        if (!$playerSession) {
            return response()->json(['error' => 'Ongeldige sessie. Start een nieuw spel.'], 401);
        }

        if ($playerSession->current_room_id != $room_Id) {
            return response()->json(['error' => 'Je moet in de kamer zijn om objecten te bekijken.'], 403);
        }
        
        $parentObject = GameObject::where('room_id', $room_Id)
            ->where('name', $objectName)
            ->where('is_visible', true)
            ->first();
        
        if (!$parentObject) {
            return response()->json(['error' => "Object '{$objectName}' niet gevonden in deze kamer."], 404);
        }
        
        $subObject = GameObject::where('parent_id', $parentObject->id)
            ->where('name', $subObjectName)
            ->where('is_visible', true)
            ->first();
        
        if (!$subObject) {
            return response()->json(['error' => "Subobject '{$subObjectName}' niet gevonden in '{$objectName}'."], 404);
        }
        
        $containedItems = GameObject::where('parent_id', $subObject->id)
            ->where('is_visible', true)
            ->get();
        
        $response = [
            'location' => "{$parentObject->name} - {$subObject->name}",
            'description' => $subObject->description
        ];
        
        if ($containedItems->count() > 0) {
            $response['objects'] = $containedItems->pluck('name');
        } else if ($subObject->type === 'container') {
            $response['objects'] = [];
            $response['message'] = 'Deze ' . $subObject->name . ' lijkt leeg te zijn.';
        }
        
        if ($subObject->has_hidden_items && !$subObject->revealed_hidden) {
            $hiddenItems = GameObject::where('parent_id', $subObject->id)
                ->where('is_visible', false)
                ->get();
                
            if ($hiddenItems->count() > 0) {
                foreach ($hiddenItems as $item) {
                    $item->is_visible = true;
                    $item->save();
                }
                
                $subObject->revealed_hidden = true;
                $subObject->save();
                
                $response['message'] = 'Je hebt iets verborgens gevonden!';
                $response['objects'] = GameObject::where('parent_id', $subObject->id)
                    ->where('is_visible', true)
                    ->pluck('name');
            }
        }
        
        return response()->json($response);
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
