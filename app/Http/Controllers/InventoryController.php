<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\GameObject;
use App\Models\PlayerSession;
use App\Models\Inventory;

class InventoryController extends Controller
{
    public function viewInventory(Request $request)
    {
        $playerSession = $this->getPlayerSession($request);
        
        if (!$playerSession) {
            return response()->json(['error' => 'Invalid session. Please start a new game.'], 401);
        }
        
        $inventoryItems = Inventory::where('player_session_id', $playerSession->id)
            ->with('gameObject')
            ->get();
        
        $items = $inventoryItems->map(function($item) {
            return [
                'name' => $item->gameObject->name,
                'description' => $item->gameObject->description
            ];
        });
        
        return response()->json([
            'inventory' => $items
        ]);
    }
    
    public function takeItem(Request $request, $roomId, $objectName, $subObjectName, $itemName)
    {
        $playerSession = $this->getPlayerSession($request);
        
        if (!$playerSession) {
            return response()->json(['error' => 'Invalid session. Please start a new game.'], 401);
        }
        
        if ($playerSession->current_room_id != $roomId) {
            return response()->json(['error' => 'You need to be in the room to take items.'], 403);
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
        
        $item = GameObject::where('parent_id', $subObject->id)
            ->where('name', $itemName)
            ->where('is_visible', true)
            ->where('is_takeable', true)
            ->first();
        
        if (!$item) {
            return response()->json(['error' => "Item '{$itemName}' not found or cannot be taken."], 404);
        }
        
        Inventory::create([
            'player_session_id' => $playerSession->id,
            'game_object_id' => $item->id,
            'acquired_at' => now()
        ]);
        
        $item->is_visible = false;
        $item->is_taken = true;
        $item->save();
        
        $inventoryItems = Inventory::where('player_session_id', $playerSession->id)
            ->with('gameObject')
            ->get()
            ->pluck('gameObject.name');
        
        return response()->json([
            'message' => "You have taken the {$itemName}!",
            'inventory' => $inventoryItems
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
