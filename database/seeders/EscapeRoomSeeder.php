<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\GameObject;

class EscapeRoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create rooms
        $room1 = Room::create([
            'name' => 'room1',
            'description' => 'You are in a dimly lit room with old furniture and a locked door to the east.',
            'adjacent_rooms' => json_encode([2]), // Room 2 is adjacent
            'is_final_room' => false
        ]);

        $room2 = Room::create([
            'name' => 'room2',
            'description' => 'This room is brighter with a large window. There is a sturdy door to the north and a passage back to the previous room.',
            'adjacent_rooms' => json_encode([1, 3]), // Room 1 and 3 are adjacent
            'is_final_room' => false
        ]);

        $room3 = Room::create([
            'name' => 'room3',
            'description' => 'This appears to be a small study with bookshelves and a desk. There\'s a heavy locked door that seems to lead outside.',
            'adjacent_rooms' => json_encode([2]), // Only room 2 is adjacent
            'is_final_room' => true
        ]);

        // ======== ROOM 1 OBJECTS ========
        // Cabinet in Room 1
        $cabinet = GameObject::create([
            'name' => 'cabinet',
            'description' => 'A large wooden cabinet with two doors - left and right.',
            'room_id' => $room1->id,
            'type' => 'container',
            'is_visible' => true,
            'is_takeable' => false
        ]);

        // Left door of cabinet
        $cabinetLeftDoor = GameObject::create([
            'name' => 'leftdoor',
            'description' => 'The left door of the cabinet.',
            'room_id' => $room1->id,
            'parent_id' => $cabinet->id,
            'type' => 'container',
            'is_visible' => true,
            'is_takeable' => false,
            'has_hidden_items' => true
        ]);

        // Right door of cabinet
        $cabinetRightDoor = GameObject::create([
            'name' => 'rightdoor',
            'description' => 'The right door of the cabinet.',
            'room_id' => $room1->id,
            'parent_id' => $cabinet->id,
            'type' => 'container',
            'is_visible' => true,
            'is_takeable' => false
        ]);

        // Key inside left door of cabinet
        $key2 = GameObject::create([
            'name' => 'key2',
            'description' => 'A small brass key with the number 2 engraved on it.',
            'room_id' => $room1->id,
            'parent_id' => $cabinetLeftDoor->id,
            'type' => 'key',
            'is_visible' => false, // Hidden until cabinet is examined
            'is_takeable' => true
        ]);

        // Old letter inside left door
        $oldLetter = GameObject::create([
            'name' => 'old letter',
            'description' => 'A weathered letter that reads: "Remember to check under the desk in room 2".',
            'room_id' => $room1->id,
            'parent_id' => $cabinetLeftDoor->id,
            'type' => 'item',
            'is_visible' => false, // Hidden until cabinet is examined
            'is_takeable' => true
        ]);

        // Table in Room 1
        $table = GameObject::create([
            'name' => 'table',
            'description' => 'A wooden table with a drawer.',
            'room_id' => $room1->id,
            'type' => 'container',
            'is_visible' => true,
            'is_takeable' => false
        ]);

        // Drawer in table
        $drawer = GameObject::create([
            'name' => 'drawer',
            'description' => 'A small drawer in the table.',
            'room_id' => $room1->id,
            'parent_id' => $table->id,
            'type' => 'container',
            'is_visible' => true,
            'is_takeable' => false
        ]);

        // Pen in drawer
        GameObject::create([
            'name' => 'pen',
            'description' => 'An ordinary pen.',
            'room_id' => $room1->id,
            'parent_id' => $drawer->id,
            'type' => 'item',
            'is_visible' => true,
            'is_takeable' => true
        ]);

        // Door to Room 2
        GameObject::create([
            'name' => 'door to room2',
            'description' => 'A locked door that leads to another room.',
            'room_id' => $room1->id,
            'type' => 'door',
            'is_visible' => true,
            'is_takeable' => false,
            'is_locked' => true
        ]);

        // ======== ROOM 2 OBJECTS ========
        // Desk in Room 2
        $desk = GameObject::create([
            'name' => 'desk',
            'description' => 'A large wooden desk with a compartment underneath.',
            'room_id' => $room2->id,
            'type' => 'container',
            'is_visible' => true,
            'is_takeable' => false
        ]);

        // Under desk compartment
        $underDesk = GameObject::create([
            'name' => 'under desk',
            'description' => 'A hidden compartment under the desk.',
            'room_id' => $room2->id,
            'parent_id' => $desk->id,
            'type' => 'container',
            'is_visible' => false, // Not immediately visible until desk is examined
            'is_takeable' => false,
            'has_hidden_items' => true
        ]);

        // Key to Room 3 hidden under desk
        GameObject::create([
            'name' => 'key3',
            'description' => 'A silver key with the number 3 engraved on it.',
            'room_id' => $room2->id,
            'parent_id' => $underDesk->id,
            'type' => 'key',
            'is_visible' => true, // Visible once under desk is found
            'is_takeable' => true
        ]);

        // Bookshelf in Room 2
        $bookshelf = GameObject::create([
            'name' => 'bookshelf',
            'description' => 'A tall bookshelf filled with dusty books of various sizes.',
            'room_id' => $room2->id,
            'type' => 'container',
            'is_visible' => true,
            'is_takeable' => false
        ]);

        // Red book on bookshelf
        $redBook = GameObject::create([
            'name' => 'red book',
            'description' => 'A bright red book titled "Secrets of the Ancient Temple".',
            'room_id' => $room2->id,
            'parent_id' => $bookshelf->id,
            'type' => 'container', // It's a container because it has a hidden compartment
            'is_visible' => true,
            'is_takeable' => true
        ]);

        // Hidden note in red book
        GameObject::create([
            'name' => 'hidden note',
            'description' => 'A folded piece of paper with a cryptic message: "The final exit requires the golden key."',
            'room_id' => $room2->id,
            'parent_id' => $redBook->id,
            'type' => 'item',
            'is_visible' => false, // Only visible when examining the red book
            'is_takeable' => true
        ]);

        // Window in Room 2
        GameObject::create([
            'name' => 'window',
            'description' => 'A large window that lets in sunlight. It\'s firmly sealed and cannot be opened.',
            'room_id' => $room2->id,
            'type' => 'scenery',
            'is_visible' => true,
            'is_takeable' => false
        ]);

        // Door to Room 3
        GameObject::create([
            'name' => 'door to room3',
            'description' => 'A sturdy wooden door with a shiny silver lock. It leads north to another room.',
            'room_id' => $room2->id,
            'type' => 'door',
            'is_visible' => true,
            'is_takeable' => false,
            'is_locked' => true
        ]);

        // Door back to Room 1
        GameObject::create([
            'name' => 'door to room1',
            'description' => 'The door leading back to the first room.',
            'room_id' => $room2->id,
            'type' => 'door',
            'is_visible' => true,
            'is_takeable' => false,
            'is_locked' => false // Already unlocked
        ]);

        // ======== ROOM 3 OBJECTS ========
        // Desk in Room 3
        $studyDesk = GameObject::create([
            'name' => 'study desk',
            'description' => 'An elegant desk with several drawers. It appears to be the main workspace in this study.',
            'room_id' => $room3->id,
            'type' => 'container',
            'is_visible' => true,
            'is_takeable' => false
        ]);

        // Desk drawer
        $studyDrawer = GameObject::create([
            'name' => 'desk drawer',
            'description' => 'The middle drawer of the study desk. It appears to be locked.',
            'room_id' => $room3->id,
            'parent_id' => $studyDesk->id,
            'type' => 'container',
            'is_visible' => true,
            'is_takeable' => false,
            'is_locked' => true
        ]);

        // Desk drawer key
        $drawerKey = GameObject::create([
            'name' => 'small key',
            'description' => 'A tiny key that might fit a desk drawer.',
            'room_id' => $room3->id,
            'type' => 'key',
            'is_visible' => true,
            'is_takeable' => true
        ]);

        // Bookshelf in Room 3
        $studyBookshelf = GameObject::create([
            'name' => 'study bookshelf',
            'description' => 'A mahogany bookshelf filled with leather-bound books and academic journals.',
            'room_id' => $room3->id,
            'type' => 'container',
            'is_visible' => true,
            'is_takeable' => false
        ]);

        // Secret lever behind books
        $secretLever = GameObject::create([
            'name' => 'secret lever',
            'description' => 'A hidden lever behind the books on the shelf.',
            'room_id' => $room3->id,
            'parent_id' => $studyBookshelf->id,
            'type' => 'mechanism',
            'is_visible' => false, // Hidden until bookshelf is thoroughly examined
            'is_takeable' => false
        ]);

        // Safe revealed by secret lever
        $safe = GameObject::create([
            'name' => 'wall safe',
            'description' => 'A small wall safe hidden behind a false panel. It requires a combination to open.',
            'room_id' => $room3->id,
            'type' => 'container',
            'is_visible' => false, // Only visible after lever is pulled
            'is_takeable' => false,
            'is_locked' => true
        ]);

        // Exit door in Room 3
        GameObject::create([
            'name' => 'exit door',
            'description' => 'A heavy door that appears to lead outside. It has a golden lock.',
            'room_id' => $room3->id,
            'type' => 'door',
            'is_visible' => true,
            'is_takeable' => false,
            'is_locked' => true
        ]);

        // Golden key for exit (inside locked drawer)
        GameObject::create([
            'name' => 'golden key',
            'description' => 'A beautiful golden key that seems important. It will likely open the final exit.',
            'room_id' => $room3->id,
            'parent_id' => $studyDrawer->id,
            'type' => 'key',
            'is_visible' => true, // Visible once drawer is unlocked
            'is_takeable' => true
        ]);

        // Door back to Room 2
        GameObject::create([
            'name' => 'door to room2',
            'description' => 'The door leading back to the second room.',
            'room_id' => $room3->id,
            'type' => 'door',
            'is_visible' => true,
            'is_takeable' => false,
            'is_locked' => false // Already unlocked
        ]);
    }
}