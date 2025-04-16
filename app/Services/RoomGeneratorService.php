<?php

namespace App\Services;

use App\Models\Room;
use App\Models\GameObject;
use App\Models\PlayerSession;
use App\Models\Inventory;
use Illuminate\Support\Str;

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
                'old book' => [
                    'description' => 'An ancient looking book with strange symbols.',
                    'type' => 'container',
                    'is_visible' => true,
                    'is_takeable' => true,
                    'has_hidden_items' => true,
                ],
                'secret lever' => [
                    'description' => 'A hidden lever behind some books.',
                    'type' => 'mechanism',
                    'is_visible' => false,
                    'is_takeable' => false,
                    'puzzle_type' => 'trigger',
                ]
            ]
        ],
        'cabinet' => [
            'description' => 'A large wooden cabinet with doors.',
            'type' => 'container',
            'is_visible' => true,
            'is_takeable' => false,
            'children' => [
                'cabinet door' => [
                    'description' => 'A cabinet door that can be opened.',
                    'type' => 'container',
                    'is_visible' => true,
                    'is_takeable' => false,
                ]
            ]
        ],
        'safe' => [
            'description' => 'A metal safe with a combination lock.',
            'type' => 'container',
            'is_visible' => true,
            'is_takeable' => false,
            'is_locked' => true,
            'puzzle_type' => 'combination',
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
            'description' => '>generateA pattern that needs to be completed.',
            'difficulty' => [2, 4],
        ],
        'color_sequence' => [
            'description' => 'A sequence of colors that must be matched.',
            'difficulty' => [1, 3],
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
        
        
        foreach ($rooms as $room) {
            $this->generateRoomObjects($room);
        }
        
        return $rooms;
    }
    
    private function generateRoomObjects($room)
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
            
            
            $this->createFinalRoomGoldenKey($room->id);
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
        
        
        if (!$room->is_final_room) {
            $adjacentRooms = json_decode($room->adjacent_rooms, true);
            if (is_array($adjacentRooms)) {
                foreach ($adjacentRooms as $adjRoomId) {
                    if ($adjRoomId > $room->id) {
                        $this->createKey($room->id, $adjRoomId);
                    }
                }
            }
        } else {
            
            $previousRoomId = $room->id - 1;
            if ($previousRoomId > 0) {
                $this->createGoldenKey($previousRoomId);
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
            'description' => 'A heavy door that appears to lead outside. It has a golden lock.',
            'room_id' => $roomId,
            'type' => 'door',
            'is_visible' => true,
            'is_takeable' => false,
            'is_locked' => true,
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
            'is_visible' => true,
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
}