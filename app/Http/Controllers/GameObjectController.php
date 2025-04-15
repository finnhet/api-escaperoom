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
            return response()->json(['error' => 'Invalid session. Please start a new game.'], 401);
        }
        
        if ($playerSession->current_room_id != $roomId) {
            return response()->json(['error' => 'You need to be in the room to look at objects.'], 403);
        }
        
        $object = GameObject::where('room_id', $roomId)
            ->where('name', $objectName)
            ->where('is_visible', true)
            ->first();
        
        if (!$object) {
            return response()->json(['error' => "Object '{$objectName}' not found in this room."], 404);
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
            $response['message'] = 'This ' . $object->name . ' appears to be empty.';
        }
        
        return response()->json($response);
    }
    

    public function lookSubObject(Request $request, $roomId, $objectName, $subObjectName)
    {
        $playerSession = $this->getPlayerSession($request);
        
        if (!$playerSession) {
            return response()->json(['error' => 'Invalid session. Please start a new game.'], 401);
        }
        
        if ($playerSession->current_room_id != $roomId) {
            return response()->json(['error' => 'You need to be in the room to look at objects.'], 403);
        }
        
        $parentObject = GameObject::where('room_id', $roomId)
            ->where('name', $objectName)
            ->where('is_visible', true)
            ->first();
        
        if (!$parentObject) {
            return response()->json(['error' => "Object '{$objectName}' not found in this room."], 404);
        }
        
        $subObject = GameObject::where('parent_id', $parentObject->id)
            ->where('name', $subObjectName)
            ->where('is_visible', true)
            ->first();
        
        if (!$subObject) {
            return response()->json(['error' => "Sub-object '{$subObjectName}' not found in '{$objectName}'."], 404);
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
            $response['message'] = 'This ' . $subObject->name . ' appears to be empty.';
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
                
                $response['message'] = 'You found something hidden!';
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
