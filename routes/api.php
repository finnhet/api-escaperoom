<?php

// Routes file: routes/api.php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\GameObjectController;
use App\Http\Controllers\InventoryController;

Route::post('/start-game', [RoomController::class, 'startGame']);

Route::get('/room/{roomId}/look', [RoomController::class, 'lookRoom']);
Route::post('/room/{nextRoomId}/open', [RoomController::class, 'openRoom']);

Route::get('/room/{roomId}/{objectName}/look', [GameObjectController::class, 'lookObject']);
Route::get('/room/{roomId}/{objectName}/{subObjectName}/look', [GameObjectController::class, 'lookSubObject']);
Route::post('/room/{roomId}/{objectName}/{subObjectName}/take-{itemName}', [InventoryController::class, 'takeItem']);

Route::get('/inventory', [InventoryController::class, 'viewInventory']);    