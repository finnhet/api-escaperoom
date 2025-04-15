<?php

// Routes file: routes/api.php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\GameObjectController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PuzzleController;

// Game management
Route::post('/start-game', [RoomController::class, 'startGame']);
Route::post('/finish-game', [RoomController::class, 'finishGame']);

// Room navigation
Route::get('/room/{roomId}/look', [RoomController::class, 'lookRoom']);
Route::post('/room/{nextRoomId}/open', [RoomController::class, 'openRoom']);

// Object interaction
Route::get('/room/{roomId}/{objectName}/look', [GameObjectController::class, 'lookObject']);
Route::get('/room/{roomId}/{objectName}/{subObjectName}/look', [GameObjectController::class, 'lookSubObject']);
Route::post('/room/{roomId}/{objectName}/{subObjectName}/take-{itemName}', [InventoryController::class, 'takeItem']);

// Inventory management
Route::get('/inventory', [InventoryController::class, 'viewInventory']);

// Puzzle interactions
Route::post('/room/{roomId}/{objectName}/solve-puzzle', [PuzzleController::class, 'solvePuzzle']);
Route::post('/room/{roomId}/{objectName}/pull-lever', [PuzzleController::class, 'pullLever']);
Route::post('/room/{roomId}/{objectName}/unlock', [PuzzleController::class, 'unlockWithKey']);
Route::post('/room/{roomId}/{objectName}/enter-combination', [PuzzleController::class, 'enterCombination']);