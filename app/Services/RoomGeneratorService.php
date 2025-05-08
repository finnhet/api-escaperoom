<?php

namespace App\Services;

use App\Models\Room;
use App\Models\GameObject;
use App\Models\PlayerSession;
use App\Models\Inventory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RoomGeneratorService
{
    private $roomTemplates = [
        'laboratory' => [
            'name' => 'Laboratory',
            'description' => 'A scientific laboratory with various chemistry equipment and strange devices.',
            'difficulty' => [1, 3],
        ],
        'library' => [
            'name' => 'Library',
            'description' => 'A dusty old library filled with ancient tomes and manuscripts.',
            'difficulty' => [2, 4],
        ],
        'cellar' => [
            'name' => 'Cellar',
            'description' => 'A dark, damp cellar with cobwebs and strange noises.',
            'difficulty' => [3, 5],
        ],
        'study' => [
            'name' => 'Study',
            'description' => 'An elegant study with bookshelves and a large wooden desk.',
            'difficulty' => [2, 3],
        ],
        'kitchen' => [
            'name' => 'Kitchen',
            'description' => 'An old kitchen with mysterious cooking tools and ingredients.',
            'difficulty' => [1, 2],
        ],
        'vault' => [
            'name' => 'Vault',
            'description' => 'A secure vault with various mysterious locked containers.',
            'difficulty' => [4, 5],
        ],
    ];
    
    private $objectTemplates = [
        'desk' => [
            'description' => 'A sturdy wooden desk with drawers.',
            'type' => 'container',
            'is_visible' => true,
            'is_takeable' => false,
            'children' => [
                'drawer' => [
                    'description' => 'A drawer that might contain something useful.',
                    'type' => 'container',
                    'is_visible' => true,
                    'is_takeable' => false,
                    'is_locked' => [true, false],
                ]
            ]
        ],
        'bookshelf' => [
            'description' => 'A tall bookshelf filled with various books.',
            'type' => 'container',
            'is_visible' => true,
            'is_takeable' => false,
            'children' => [
                'harry snotter' => [
                    'description' => 'A book about a wizard named Harry Snotter.',
                    'type' => 'container',
                    'is_visible' => true,
                    'is_takeable' => false,
                    'has_hidden_items' => true,
                ],
                'warrior cats' => [
                    'description' => 'A book about cats that are warriors.',
                    'type' => 'container',
                    'is_visible' => true,
                    'is_takeable' => false,
                    'has_hidden_items' => true,
                ],
                'The Communist Manifesto' => [
                    'description' => 'A book about communism.',
                    'type' => 'container',
                    'is_visible' => true,
                    'is_takeable' => false,
                    'has_hidden_items' => true,
                ]        
            ]
        ],
        'cabinet' => [
            'description' => 'A large wooden cabinet with doors.',
            'type' => 'container',
            'is_visible' => true,
            'is_takeable' => false,
            'children' => [
                'cabinet right door' => [
                    'description' => 'A cabinet door that can be opened.',
                    'type' => 'container',
                    'is_visible' => true,
                    'is_takeable' => false,
                    'has_hidden_items' => true,
                ],
                'cabinet left door' => [
                    'description' => 'A cabinet door that can be opened.',
                    'type' => 'container',
                    'is_visible' => true,
                    'is_takeable' => false,
                    'has_hidden_items' => true,
                ]
            ]
        ],
        'trunk' => [
            'description' => 'An old wooden trunk with a rusty lock.',
            'type' => 'container',
            'is_visible' => true,
            'is_takeable' => false,
            'has_hidden_items' => true,
        ],
        'wardrobe' => [
            'description' => 'A tall wooden wardrobe with double doors.',
            'type' => 'container',
            'is_visible' => true,
            'is_takeable' => false,
            'children' => [
            'wardrobe left door' => [
                'description' => 'The left door of the wardrobe.',
                'type' => 'container',
                'is_visible' => true,
                'is_takeable' => false,
                'has_hidden_items' => true,
            ],
            'wardrobe right door' => [
                'description' => 'The right door of the wardrobe.',
                'type' => 'container',
                'is_visible' => true,
                'is_takeable' => false,
                'has_hidden_items' => true,
            ]
            ]
        ],
        'chest' => [
            'description' => 'A decorative chest with intricate carvings.',
            'type' => 'container',
            'is_visible' => true,
            'is_takeable' => false,
            'has_hidden_items' => true,
        ],
        'painting' => [
            'description' => 'A mysterious painting that seems to hide something.',
            'type' => 'container',
            'is_visible' => true,
            'is_takeable' => false,
            'has_hidden_items' => true,
        ],
    ];
    
    private $puzzleTypes = [
        'combination' => [
            'description' => 'A combination lock that requires a specific sequence of numbers.',
            'difficulty' => [2, 5],
        ],
        'riddle' => [
            'description' => 'A cryptic riddle that must be solved.',
            'difficulty' => [1, 4],
        ],
        'pattern' => [
            'description' => 'A pattern that needs to be completed.',
            'difficulty' => [2, 4],
        ],
        'color_sequence' => [
            'description' => 'A sequence of colors that must be matched.',
            'difficulty' => [1, 3],
        ],
        'paper_code' => [
            'description' => 'A code written on a paper that unlocks a safe.',
            'difficulty' => [1, 2],
        ],
        'multi_paper_riddle' => [
            'description' => 'Multiple papers with code fragments that must be combined.',
            'difficulty' => [3, 5],
        ],
        'repair_key' => [
            'description' => 'A broken key that needs to be repaired with glue.',
            'difficulty' => [2, 3],
        ],
    ];
    
    public function generateRooms($roomCount = 3)
    {
        Inventory::query()->delete();
        PlayerSession::query()->delete();
   
        $gameObjects = GameObject::all();
        
        foreach ($gameObjects as $object) {
            $object->parent_id = null;
            $object->save();
        }
        
        GameObject::query()->delete();
        Room::query()->delete();

        DB::statement('ALTER TABLE rooms AUTO_INCREMENT = 1');

        $rooms = [];
        $templates = array_keys($this->roomTemplates);
        $finalRoomTemplate = $templates[array_rand($templates)];
        
        for ($i = 1; $i <= $roomCount; $i++) {
            $isLastRoom = ($i == $roomCount);            

            $template = $isLastRoom ? $finalRoomTemplate : $templates[array_rand($templates)];            

            $roomName = 'room' . $i;
            $templateData = $this->roomTemplates[$template];
            
            $difficulty = rand($templateData['difficulty'][0], $templateData['difficulty'][1]);
            
            $room = Room::create([
                'name' => $roomName,
                'description' => $templateData['description'],
                'adjacent_rooms' => json_encode([]),  
                'is_final_room' => $isLastRoom,
                'room_type' => $isLastRoom ? 'final' : 'standard',
                'template_id' => $template,
                'difficulty' => $difficulty
            ]);
            
            $rooms[] = $room;
            
            if (!$isLastRoom) {
                $key = array_search($template, $templates);
                if ($key !== false) {
                    unset($templates[$key]);
                    $templates = array_values($templates); 
                }
            }
        }
        
        for ($i = 0; $i < count($rooms); $i++) {
            $adjacentRooms = [];
            
            if ($i > 0) {
                $adjacentRooms[] = $rooms[$i-1]->id;
            }
            
            if ($i < count($rooms) - 1) {
                $adjacentRooms[] = $rooms[$i+1]->id;
            }
            
            $rooms[$i]->adjacent_rooms = json_encode($adjacentRooms);
            $rooms[$i]->save();
        }
        
        
        $minPuzzles = ceil($roomCount / 2);
        $puzzleCount = 1;

        if ($roomCount > 1) {
            $puzzleCount++;
        }
        
        $roomsWithoutPuzzles = [];
        for ($i = 2; $i < $roomCount; $i++) {
            $roomsWithoutPuzzles[] = $i;
        }
        
        shuffle($roomsWithoutPuzzles);
        
        $additionalPuzzlesNeeded = max(0, $minPuzzles - $puzzleCount);
        $roomsToGetPuzzles = array_slice($roomsWithoutPuzzles, 0, $additionalPuzzlesNeeded);
        
        foreach ($rooms as $room) {
            $shouldHavePuzzle = $room->is_final_room || $room->id === 1 || in_array($room->id, $roomsToGetPuzzles);
            $this->generateRoomObjects($room, $shouldHavePuzzle);
        }
        
        return $rooms;
    }
    
    private function generateRoomObjects($room, $shouldHavePuzzle = false)
    {
        $objectCount = $room->is_final_room ? 4 : rand(2, 4);
        $templates = array_keys($this->objectTemplates);
        
        if (!$room->is_final_room) {
            $adjacentRooms = json_decode($room->adjacent_rooms, true);
            if (!empty($adjacentRooms)) {
                foreach ($adjacentRooms as $adjRoomId) {
                    if ($adjRoomId > $room->id) {  
                        $this->createDoor($room->id, $adjRoomId);
                    }
                }
            }
        } else {
            $this->createExitDoor($room->id);
        }

        $usedTemplates = [];
        for ($i = 0; $i < $objectCount; $i++) {
            $template = $templates[array_rand($templates)];
            
            if (in_array($template, $usedTemplates)) {
                continue;
            }
            
            $this->createObjectFromTemplate($room->id, $template, null);
            $usedTemplates[] = $template;
        }

        $hasPuzzle = false;

        if ($room->is_final_room) {
            
            $allRoomIds = Room::pluck('id')->toArray();
            $this->createMultiPaperRiddlePuzzle($room->id, $allRoomIds);
            $hasPuzzle = true;
        } 
        else if ($room->id === 1) {
            
            $this->createPaperCodeSafePuzzle($room->id);
            $hasPuzzle = true;
        }
        else if ($shouldHavePuzzle && !$hasPuzzle) { 
            
            $this->createRepairKeyPuzzle($room->id);
            $hasPuzzle = true;
        }
    
        if (!$room->is_final_room && !$hasPuzzle) {
            $adjacentRooms = json_decode($room->adjacent_rooms, true);
            if (is_array($adjacentRooms)) {
                foreach ($adjacentRooms as $adjRoomId) {
                    if ($adjRoomId > $room->id) {
                        $this->createKey($room->id, $adjRoomId);
                    }
                }
            }
        }
        
    }
    
    private function createObjectFromTemplate($roomId, $templateName, $parentId = null)
    {
        $template = $this->objectTemplates[$templateName];
        $objectName = Str::slug($templateName, ' '); 

        foreach ($template as $key => $value) {
            if (is_array($value) && !in_array($key, ['children'])) {
                $template[$key] = $value[array_rand($value)];
            }
        }
        
        $object = GameObject::create([
            'name' => $objectName,
            'description' => $template['description'],
            'room_id' => $roomId,
            'parent_id' => $parentId,
            'type' => $template['type'],
            'is_visible' => $template['is_visible'] ?? true,
            'is_takeable' => $template['is_takeable'] ?? false,
            'is_locked' => $template['is_locked'] ?? false,
            'has_hidden_items' => $template['has_hidden_items'] ?? false,
            'puzzle_type' => $template['puzzle_type'] ?? null,
            'template_data' => json_encode(['template' => $templateName]),
        ]);
        
        
        if (isset($template['children'])) {
            foreach ($template['children'] as $childName => $childTemplate) {
                $this->createObjectFromTemplateData($roomId, $childName, $childTemplate, $object->id);
            }
        }
        
        return $object;
    }
    
    private function createObjectFromTemplateData($roomId, $objectName, $templateData, $parentId = null)
    {
        
        foreach ($templateData as $key => $value) {
            if (is_array($value) && !in_array($key, ['children'])) {
                $templateData[$key] = $value[array_rand($value)];
            }
        }
        
        
        $object = GameObject::create([
            'name' => $objectName,
            'description' => $templateData['description'],
            'room_id' => $roomId,
            'parent_id' => $parentId,
            'type' => $templateData['type'],
            'is_visible' => $templateData['is_visible'] ?? true,
            'is_takeable' => $templateData['is_takeable'] ?? false,
            'is_locked' => $templateData['is_locked'] ?? false,
            'has_hidden_items' => $templateData['has_hidden_items'] ?? false,
            'puzzle_type' => $templateData['puzzle_type'] ?? null,
        ]);
        
        
        if (isset($templateData['children'])) {
            foreach ($templateData['children'] as $childName => $childTemplate) {
                $this->createObjectFromTemplateData($roomId, $childName, $childTemplate, $object->id);
            }
        }
        
        return $object;
    }
    
    private function createDoor($fromRoomId, $toRoomId)
    {
        
        GameObject::create([
            'name' => 'door to room' . $toRoomId,
            'description' => 'A door that leads to the next room.',
            'room_id' => $fromRoomId,
            'type' => 'door',
            'is_visible' => true,
            'is_takeable' => false,
            'is_locked' => true,
        ]);
        
        
        if ($fromRoomId > 1) {
            GameObject::create([
                'name' => 'door to room' . $fromRoomId,
                'description' => 'A door that leads back to the previous room.',
                'room_id' => $toRoomId,
                'type' => 'door',
                'is_visible' => true,
                'is_takeable' => false,
                'is_locked' => false,
            ]);
        }
    }
    
    private function createExitDoor($roomId)
    {
        GameObject::create([
            'name' => 'exit door',
            'description' => 'A heavy door that appears to lead outside. It has a 6-digit combination lock.',
            'room_id' => $roomId,
            'type' => 'door',
            'is_visible' => true,
            'is_takeable' => false,
            'is_locked' => true,
            'puzzle_type' => 'combination',
        ]);
    }
    
    private function createKey($roomId, $forRoomId)
    {
        
        $containers = GameObject::where('room_id', $roomId)
            ->where('type', 'container')
            ->where('is_locked', false)
            ->where('is_visible', true)
            ->get();
        
        $parentId = null;
        
        
        if ($containers->count() > 0) {
            $container = $containers->random();
            $parentId = $container->id;
        }
        
        GameObject::create([
            'name' => 'key' . $forRoomId,
            'description' => 'A key with the number ' . $forRoomId . ' engraved on it.',
            'room_id' => $roomId,
            'parent_id' => $parentId,
            'type' => 'key',
            'is_visible' => true, 
            'is_takeable' => true,
        ]);
        
        
        $room = Room::find($roomId);
        if ($room) {
            
            if (!str_contains($room->description, 'key')) {
                $room->description .= ' There might be a key somewhere in this room.';
                $room->save();
            }
        }
    }
    
    private function createGoldenKey($roomId)
    {
        
        $containers = GameObject::where('room_id', $roomId)
            ->where('type', 'container')
            ->where('is_visible', true)
            ->get();
        
        $parentId = null;
        
        
        if ($containers->count() > 0) {
            $container = $containers->random();
            $parentId = $container->id;
            
            
            if ($container->is_locked) {
                
                $room = Room::find($roomId);
                if ($room && !str_contains($room->description, 'locked')) {
                    $room->description .= ' There\'s a locked container here that might hold something valuable.';
                    $room->save();
                }
            } else {
                
                $container->is_locked = true;
                $container->save();
            }
        }
        
        
        GameObject::create([
            'name' => 'golden key',
            'description' => 'A beautiful golden key that seems important. It will likely open the final exit.',
            'room_id' => $roomId,
            'parent_id' => $parentId,
            'type' => 'key',
            'is_visible' => false,
            'is_takeable' => true,
        ]);
        
        
        if (!$parentId) {
            $room = Room::find($roomId);
            if ($room) {
                $room->description .= ' You notice something golden glinting in the corner.';
                $room->save();
            }
        }
    }
    
    private function createFinalRoomGoldenKey($roomId)
    {
        GameObject::create([
            'name' => 'golden key',
            'description' => 'A beautiful golden key that seems important. It will likely open the final exit.',
            'room_id' => $roomId,
            'type' => 'key',
            'is_visible' => true,
            'is_takeable' => true,
        ]);
    }

    private function createPaperCodeSafePuzzle($roomId)
    {
        
        $safe = GameObject::create([
            'name' => 'small safe',
            'description' => 'A small safe with a combination lock. It looks important.',
            'room_id' => $roomId,
            'type' => 'container',
            'is_visible' => true,
            'is_takeable' => false,
            'is_locked' => true,
            'puzzle_type' => 'combination',
            'has_hidden_items' => true,
        ]);
        
        
        $code = '';
        for ($i = 0; $i < 4; $i++) {
            $code .= rand(0, 9);
        }
        
        
        $safe->puzzle_solution = $code;
        $safe->puzzle_hint = "You need to find the code written on a paper somewhere in this room.";
        $safe->save();
        
        
        $containers = GameObject::where('room_id', $roomId)
            ->where('type', 'container')
            ->where('name', '!=', 'small safe')
            ->get();
            
        $parentId = null;
        if ($containers->count() > 0) {
            $container = $containers->random();
            $parentId = $container->id;
        }
        
        
        $paper = GameObject::create([
            'name' => 'crumpled paper',
            'description' => 'A crumpled piece of paper with the numbers ' . $code . ' written on it.',
            'room_id' => $roomId,
            'parent_id' => $parentId,
            'type' => 'item',
            'is_visible' => true,
            'is_takeable' => true,
        ]);
        
        
        $adjacentRooms = json_decode(Room::find($roomId)->adjacent_rooms, true);
        if (!empty($adjacentRooms)) {
            foreach ($adjacentRooms as $adjRoomId) {
                if ($adjRoomId > $roomId) {
                    
                    GameObject::create([
                        'name' => 'key' . $adjRoomId,
                        'description' => 'A key with the number ' . $adjRoomId . ' engraved on it.',
                        'room_id' => $roomId,
                        'parent_id' => $safe->id,
                        'type' => 'key',
                        'is_visible' => true,
                        'is_takeable' => true,
                    ]);
                    break;
                }
            }
        }
        
        
        $room = Room::find($roomId);
        $room->description .= ' You notice a safe in the corner and what appears to be a crumpled paper somewhere.';
        $room->save();
    }

    private function createMultiPaperRiddlePuzzle($roomId, $allRoomIds)
    {
        
        $codeDigits = [];
        for ($i = 0; $i < 6; $i++) {
            $codeDigits[] = rand(0, 9);
        }
        
        $fullCode = implode('', $codeDigits);
        $codePart1 = $codeDigits[0] . $codeDigits[1];
        $codePart2 = $codeDigits[2] . $codeDigits[3];
        $codePart3 = $codeDigits[4] . $codeDigits[5];
        
        
        $exitDoor = GameObject::where('name', 'exit door')
            ->where('room_id', $roomId)
            ->first();
            
        if ($exitDoor) {
            $exitDoor->puzzle_type = 'combination';
            $exitDoor->puzzle_solution = $fullCode;
            $exitDoor->puzzle_hint = "You need to find and combine the code fragments from three paper pieces scattered through the rooms.";
            $exitDoor->save();
        } else {
            
            
            $exitDoor = GameObject::create([
                'name' => 'exit door',
                'description' => 'A heavy door that appears to lead outside. It has a 6-digit combination lock.',
                'room_id' => $roomId,
                'type' => 'door',
                'is_visible' => true,
                'is_takeable' => false,
                'is_locked' => true,
                'puzzle_type' => 'combination',
                'puzzle_solution' => $fullCode,
                'puzzle_hint' => "You need to find and combine the code fragments from three paper pieces scattered through the rooms.",
            ]);
        }
        
        
        $room = Room::find($roomId);
        $room->description .= ' There seems to be some sort of puzzle here that requires code fragments written on papers.';
        $room->save();
        
        
        $placementRooms = $allRoomIds;
        shuffle($placementRooms);
        
        
        $this->createRiddlePaperPiece(
            array_shift($placementRooms), 
            'torn paper 1',
            'A torn piece of paper with the numbers ' . $codePart1 . ' at the beginning of what seems like a sequence.'
        );
        
        
        $this->createRiddlePaperPiece(
            array_shift($placementRooms), 
            'torn paper 2',
            'A torn piece of paper with the numbers ' . $codePart2 . ' that seems to be part of a sequence.'
        );
        
        
        $this->createRiddlePaperPiece(
            array_shift($placementRooms),
            'torn paper 3',
            'A torn piece of paper with the numbers ' . $codePart3 . ' at the end of what seems like a sequence.'
        );
    }
    
    private function createRiddlePaperPiece($roomId, $name, $description)
    {
        
        $containers = GameObject::where('room_id', $roomId)
            ->where('type', 'container')
            ->get();
            
        $parentId = null;
        if ($containers->count() > 0) {
            $container = $containers->random();
            $parentId = $container->id;
            
            
            $isVisible = true;
            
            if (!$isVisible && $container->has_hidden_items === false) {
                $container->has_hidden_items = true;
                $container->save();
            }
        } else {
            $isVisible = true;
        }
        
        
        GameObject::create([
            'name' => $name,
            'description' => $description,
            'room_id' => $roomId,
            'parent_id' => $parentId,
            'type' => 'item',
            'is_visible' => $isVisible,
            'is_takeable' => true,
        ]);
        
        
        $room = Room::find($roomId);
        if (!str_contains($room->description, 'paper')) {
            $room->description .= ' There might be a piece of paper hidden somewhere in this room.';
            $room->save();
        }
    }

    private function createRepairKeyPuzzle($roomId)
    {
        
        $brokenKey = GameObject::create([
            'name' => 'broken key',
            'description' => 'A key that has been broken into two pieces. It might be useful if repaired.',
            'room_id' => $roomId,
            'type' => 'item',
            'is_visible' => true,
            'is_takeable' => true,
            'puzzle_type' => 'repair_key',
            'puzzle_solution' => 'glue',
        ]);
        
        
        $containers = GameObject::where('room_id', $roomId)
            ->where('type', 'container')
            ->get();
            
        $parentId = null;
        if ($containers->count() > 0) {
            $container = $containers->random();
            $parentId = $container->id;
        }
        
        
        GameObject::create([
            'name' => 'glue',
            'description' => 'A small tube of strong adhesive glue.',
            'room_id' => $roomId,
            'parent_id' => $parentId,
            'type' => 'item',
            'is_visible' => true,
            'is_takeable' => true,
        ]);
        
        
        $adjacentRooms = json_decode(Room::find($roomId)->adjacent_rooms, true);
        $targetRoomId = null;
        
        if (!empty($adjacentRooms)) {
            foreach ($adjacentRooms as $adjRoomId) {
                if ($adjRoomId > $roomId) {
                    $targetRoomId = $adjRoomId;
                    break;
                }
            }
        }
        
        
        if (!$targetRoomId) {
            $allRooms = Room::where('id', '!=', $roomId)->get();
            if ($allRooms->count() > 0) {
                $targetRoomId = $allRooms->random()->id;
            }
        }
        
        
        if ($targetRoomId) {
            
            GameObject::create([
                'name' => "key{$targetRoomId}",
                'description' => 'A key with the number ' . $targetRoomId . ' engraved on it.',
                'room_id' => $roomId,
                'type' => 'key',
                'is_visible' => false,  
                'is_takeable' => true,
            ]);
            
            $brokenKey->puzzle_hint = "This key seems to have the number {$targetRoomId} partially visible on it. If fixed, it might open a door.";
            $brokenKey->save();
        }
        
        
        $room = Room::find($roomId);
        $room->description .= ' You notice what appears to be a broken key and possibly something to fix it.';
        $room->save();
    }
}