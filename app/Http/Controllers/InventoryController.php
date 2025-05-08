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
            return response()->json(['error' => 'Ongeldige sessie. Start alstublieft een nieuw spel.'], 401);
        }
        
        $inventoryItems = Inventory::where('player_session_id', $playerSession->id)
            ->with('gameObject')
            ->get();

            if (!$inventoryItems) {
                return response()->json(['error' => 'Geen items in inventory gevonden.'], 404);
            }
        
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
        $room = Room::find($playerSession->current_room_id);
        $room_Id = $room->id;

        if (!$playerSession) {
            return response()->json(['error' => 'Ongeldige sessie. Start alstublieft een nieuw spel.'], 401);
        }
        
        if ($playerSession->current_room_id != $room_Id) {
            return response()->json(['error' => 'Je moet in de kamer zijn om items te kunnen pakken.'], 403);
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
            return response()->json(['error' => "Sub-object '{$subObjectName}' niet gevonden in '{$objectName}'."], 404);
        }
        
        $item = GameObject::where('parent_id', $subObject->id)
            ->where('name', $itemName)
            ->where('is_visible', true)
            ->where('is_takeable', true)
            ->first();
        
        if (!$item) {
            return response()->json(['error' => "Item '{$itemName}' niet gevonden of kan niet worden gepakt."], 404);
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
            'message' => "Je hebt de {$itemName} gepakt!",
            'inventory' => $inventoryItems
        ]);
    }
    
    public function takeItemFromContainer(Request $request, $roomId, $objectName, $itemName)
    {
        $playerSession = $this->getPlayerSession($request);
        $room = Room::find($playerSession->current_room_id);
        $room_Id = $room->id;
        
        if (!$playerSession) {
            return response()->json(['error' => 'Ongeldige sessie. Start alstublieft een nieuw spel.'], 401);
        }
        
        if ($playerSession->current_room_id != $room_Id) {
            return response()->json(['error' => 'Je moet in de kamer zijn om items te kunnen pakken.'], 403);
        }
        
        
        $container = GameObject::where('room_id', $room_Id)
            ->where('name', $objectName)
            ->where('is_visible', true)
            ->first();
        
        if (!$container) {
            return response()->json(['error' => "Object '{$objectName}' niet gevonden in deze kamer."], 404);
        }
        
        
        $item = GameObject::where('parent_id', $container->id)
            ->where('name', $itemName)
            ->where('is_visible', true)
            ->where('is_takeable', true)
            ->first();
        
        if (!$item) {
            return response()->json(['error' => "Item '{$itemName}' niet gevonden in de {$objectName} of kan niet worden gepakt."], 404);
        }
        
        
        if ($container->is_locked) {
            return response()->json(['error' => "De {$objectName} is op slot. Je moet deze eerst ontgrendelen."], 403);
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
            'message' => "Je hebt de {$itemName} uit de {$objectName} gepakt!",
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
