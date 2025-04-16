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
            return response()->json(['error' => 'Ongeldige sessie. Start een nieuw spel.'], 401);
        }
        
        $inventoryItems = Inventory::where('player_session_id', $playerSession->id)
            ->with('gameObject')
            ->get();

        if (!$inventoryItems) {
            return response()->json(['error' => 'Geen items in inventaris gevonden.'], 404);
        }
        
        $items = $inventoryItems->map(function($item) {
            return [
                'name' => $item->gameObject->name,
                'description' => $item->gameObject->description
            ];
        });
        
        return response()->json([
            'inventaris' => $items
        ]);
    }
    
    public function takeItem(Request $request, $roomId, $objectName, $itemName)
    {
        $playerSession = $this->getPlayerSession($request);
        
        if (!$playerSession) {
            return response()->json(['error' => 'Ongeldige sessie. Start een nieuw spel.'], 401);
        }
        
        if ($playerSession->current_room_id != $roomId) {
            return response()->json(['error' => 'Je moet in de kamer zijn om items op te pakken.'], 403);
        }
        
        
        $parentObject = GameObject::where('room_id', $roomId)
            ->where('name', $objectName)
            ->where('is_visible', true)
            ->first();
            
        if (!$parentObject) {
            return response()->json(['error' => "Het object '{$objectName}' is niet gevonden in deze kamer."], 404);
        }
        
        
        if ($parentObject->is_locked) {
            return response()->json(['error' => "'{$objectName}' is vergrendeld. Je moet het eerst openen."], 403);
        }
        
        
        $item = GameObject::where('parent_id', $parentObject->id)
            ->where('name', $itemName)
            ->where('is_visible', true)
            ->where('is_takeable', true)
            ->first();
            
        if (!$item) {
            return response()->json(['error' => "Item '{$itemName}' is niet gevonden in/op '{$objectName}' of kan niet worden opgepakt."], 404);
        }
        
        if ($item->is_taken) {
            return response()->json(['error' => "Dit item is al opgepakt."], 400);
        }
        
        
        $item->is_taken = true;
        $item->save();
        
        
        Inventory::create([
            'player_session_id' => $playerSession->id,
            'game_object_id' => $item->id,
            'acquired_at' => now()
        ]);
        
        return response()->json([
            'message' => "Je hebt '{$itemName}' in je inventaris gestopt.",
            'item' => [
                'name' => $item->name,
                'description' => $item->description
            ]
        ]);
    }
    
    public function takeItemFromContainer(Request $request, $roomId, $objectName, $itemName)
    {
        $playerSession = $this->getPlayerSession($request);
        
        if (!$playerSession) {
            return response()->json(['error' => 'Ongeldige sessie. Start een nieuw spel.'], 401);
        }
        
        if ($playerSession->current_room_id != $roomId) {
            return response()->json(['error' => 'Je moet in de kamer zijn om items op te pakken.'], 403);
        }
        
        $room = Room::find($roomId);
        
        if (!$room) {
            $room = Room::where('name', 'room' . $roomId)->first();
        }
        
        if (!$room) {
            return response()->json(['error' => "Kamer {$roomId} niet gevonden."], 404);
        }
        
        $container = GameObject::where('room_id', $room->id)
            ->where('name', $objectName)
            ->where('is_visible', true)
            ->first();
            
        if (!$container) {
            return response()->json(['error' => "Container '{$objectName}' niet gevonden in deze kamer."], 404);
        }
        
        if ($container->is_locked) {
            return response()->json(['error' => "'{$objectName}' is vergrendeld. Je moet het eerst openen."], 403);
        }
        
        $item = GameObject::where('parent_id', $container->id)
            ->where('name', $itemName)
            ->where('is_visible', true)
            ->where('is_takeable', true)
            ->first();
            
        if (!$item) {
            return response()->json(['error' => "Item '{$itemName}' niet gevonden in '{$objectName}' of kan niet worden opgepakt."], 404);
        }
        
        if ($item->is_taken) {
            return response()->json(['error' => "Dit item is al opgepakt."], 400);
        }
        
        $item->is_taken = true;
        $item->save();
        
        Inventory::create([
            'player_session_id' => $playerSession->id,
            'game_object_id' => $item->id,
            'acquired_at' => now()
        ]);
        
        return response()->json([
            'message' => "Je hebt '{$itemName}' uit '{$objectName}' in je inventaris gestopt.",
            'item' => [
                'name' => $item->name,
                'description' => $item->description
            ]
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
