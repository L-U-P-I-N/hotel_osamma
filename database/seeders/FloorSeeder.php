<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Floor;
use Illuminate\Support\Facades\DB;

class FloorSeeder extends Seeder
{
    public function run(): void
    {
        Floor::truncate();

        Floor::create(['floor_number' => 1, 'door_count' => 1,  'name' => 'الطابق الأول']);
        Floor::create(['floor_number' => 2, 'door_count' => 8,  'name' => 'الطابق الثاني']);
        Floor::create(['floor_number' => 3, 'door_count' => 8,  'name' => 'الطابق الثالث']);
        Floor::create(['floor_number' => 4, 'door_count' => 8,  'name' => 'الطابق الرابع']);
        Floor::create(['floor_number' => 5, 'door_count' => 8,  'name' => 'الطابق الخامس']);
        Floor::create(['floor_number' => 6, 'door_count' => 8,  'name' => 'الطابق السادس']);
    }
}
