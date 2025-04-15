<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Enhanced Escape Room API

This API provides a virtual escape room experience with randomized rooms and puzzles that you can interact with through Postman. Each time you start a new game, the room layout, objects, and puzzles will be different!

## Features

- **Randomized Room Generation**: Each game creates a unique set of rooms with 3-5 rooms by default
- **Randomly Placed Objects**: Objects and their locations vary each game
- **Interactive Puzzles**: Several types of puzzles including combinations, hidden mechanisms, and more
- **Inventory System**: Collect and use items to unlock doors and containers
- **Complete Through API**: Everything is accessible through API calls (Postman)

## How to Use

1. Clone the repository
2. Run migrations: `php artisan migrate:fresh`
3. Start the server: `php artisan serve`
4. Import the `postman_collection.json` file into Postman
5. Set your base URL environment variable to where your server is running (e.g., `http://localhost:8000`)

## Gameplay Guide

### Step 1: Start a New Game
- Use the `Start New Game` request to begin
- You can specify how many rooms to generate with the `room_count` parameter
- The response will include a session token that is automatically saved to your environment variables

### Step 2: Explore Rooms
- Use `Look Around Current Room` to see what objects are available
- Use `Look at Object` to examine objects more closely
- Use `Look at Sub-Object` to examine objects within containers

### Step 3: Solve Puzzles & Collect Items
- Use the various puzzle endpoints to interact with objects:
  - `Pull Lever`: Activate mechanisms that might reveal hidden items
  - `Enter Combination`: Try to unlock combination locks (hints may be hidden in the room)
  - `Solve Puzzle`: Solve various puzzles to progress
- Take items by using the `Take Item` endpoint

### Step 4: Navigate Between Rooms
- Use `Open Door to Next Room` to move between rooms
- You'll need the appropriate key to unlock doors

### Step 5: Complete the Game
- Find the exit door in the final room
- Unlock it with the golden key
- Use the `Finish Game` endpoint to complete the game

## Puzzle Types

1. **Combination Locks**: Require a numeric code to unlock
2. **Levers**: Pull to reveal hidden objects or mechanisms
3. **Hidden Objects**: Some items are only visible after examining containers
4. **Locked Containers**: Require keys to open

## Tips

- Always check your inventory to see what items you have
- Look at all objects in the room carefully
- Some puzzles provide hints if you get the answer wrong
- The room description often contains clues
- Keys are named after the room they unlock (e.g., "key2" unlocks the door to room 2)

Happy escaping!

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
