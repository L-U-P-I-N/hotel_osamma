<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $hotel = Hotel::first();
        $single = RoomType::where('name', 'single')->first();
        $double = RoomType::where('name', 'double')->first();
        $suite = RoomType::where('name', 'suite')->first();

        $rooms = [];
        for ($floor = 1; $floor <= 4; $floor++) {
            for ($num = 1; $num <= 5; $num++) {
                $roomNumber = $floor . str_pad($num, 2, '0', STR_PAD_LEFT);
                $type = $num <= 2 ? $single : ($num <= 4 ? $double : $suite);
                $rooms[] = [
                    'hotel_id' => $hotel->id,
                    'room_type_id' => $type->id,
                    'room_number' => $roomNumber,
                    'floor' => $floor,
                    'status' => 'available',
                ];
            }
        }

        foreach ($rooms as $room) {
            Room::create($room);
        }
    }
}
