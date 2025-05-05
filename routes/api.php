<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\GameObjectController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PuzzleController;


Route::post('/start-game', [RoomController::class, 'startGame']);
Route::post('/finish-game', [RoomController::class, 'finishGame']);
Route::post('/cheat-code', [RoomController::class, 'useCheatCode']); // Add new cheat code endpoint


Route::get('/room/{roomId}/look', [RoomController::class, 'lookRoom']);
Route::post('/room/{nextRoomId}/open', [RoomController::class, 'openRoom']);


Route::get('/room/{roomId}/{objectName}/look', [GameObjectController::class, 'lookObject']);
Route::get('/room/{roomId}/{objectName}/{subObjectName}/look', [GameObjectController::class, 'lookSubObject']);
Route::post('/room/{roomId}/{objectName}/{subObjectName}/take-{itemName}', [InventoryController::class, 'takeItem']);
Route::post('/room/{roomId}/{objectName}/take-{itemName}', [InventoryController::class, 'takeItemFromContainer']);


Route::get('/inventory', [InventoryController::class, 'viewInventory']);


Route::post('/room/{roomId}/{objectName}/solve-puzzle', [PuzzleController::class, 'solvePuzzle']);
Route::post('/room/{roomId}/{objectName}/pull-lever', [PuzzleController::class, 'pullLever']);
Route::post('/room/{roomId}/{objectName}/unlock', [PuzzleController::class, 'unlockWithKey']);
Route::post('/room/{roomId}/{objectName}/enter-combination', [PuzzleController::class, 'enterCombination']);
Route::post('/room/{roomId}/{objectName}/repair', [PuzzleController::class, 'repairKey']);