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

        $driver = \DB::getDriverName();
        if ($driver === 'sqlite') {
            \DB::statement('PRAGMA foreign_keys = OFF');
            \DB::table('rooms')->where('hotel_id', $hotel->id)->delete();
            \DB::statement('PRAGMA foreign_keys = ON');
        } else {
            \DB::statement('SET FOREIGN_KEY_CHECKS=0');
            \DB::table('rooms')->where('hotel_id', $hotel->id)->delete();
            \DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $regular = RoomType::where('hotel_id', $hotel->id)->where('name', 'غرفة عادية')->first();
        $suite   = RoomType::where('hotel_id', $hotel->id)->where('name', 'جناح')->first();
        $hall    = RoomType::where('hotel_id', $hotel->id)->where('name', 'صالة')->first();

        // Floor 1: hall only
        Room::create([
            'hotel_id'      => $hotel->id,
            'room_type_id'  => $hall->id,
            'room_number'   => '101',
            'floor'         => 1,
            'room_sub_type' => 'hall',
            'status'        => 'available',
        ]);

        // Floors 2-6: 8 doors each
        // Door 1  → Suite A/B pair  (X01A + X01B)
        // Doors 2-7 → 6 regular rooms (X02 … X07)
        // Door 8  → Suite A/B pair  (X08A + X08B)
        for ($floor = 2; $floor <= 6; $floor++) {
            $f = (string)$floor;

            // Door 1 — Suite A
            $suiteA1 = Room::create([
                'hotel_id'      => $hotel->id,
                'room_type_id'  => $suite->id,
                'room_number'   => $f . '01A',
                'floor'         => $floor,
                'room_sub_type' => 'suite_a',
                'status'        => 'available',
            ]);
            // Door 1 — Suite B
            $suiteB1 = Room::create([
                'hotel_id'       => $hotel->id,
                'room_type_id'   => $suite->id,
                'room_number'    => $f . '01B',
                'floor'          => $floor,
                'room_sub_type'  => 'suite_b',
                'linked_room_id' => $suiteA1->id,
                'status'         => 'available',
            ]);
            $suiteA1->update(['linked_room_id' => $suiteB1->id]);

            // Doors 2-7 — regular rooms
            for ($door = 2; $door <= 7; $door++) {
                Room::create([
                    'hotel_id'     => $hotel->id,
                    'room_type_id' => $regular->id,
                    'room_number'  => $f . str_pad($door, 2, '0', STR_PAD_LEFT),
                    'floor'        => $floor,
                    'status'       => 'available',
                ]);
            }

            // Door 8 — Suite A
            $suiteA8 = Room::create([
                'hotel_id'      => $hotel->id,
                'room_type_id'  => $suite->id,
                'room_number'   => $f . '08A',
                'floor'         => $floor,
                'room_sub_type' => 'suite_a',
                'status'        => 'available',
            ]);
            // Door 8 — Suite B
            $suiteB8 = Room::create([
                'hotel_id'       => $hotel->id,
                'room_type_id'   => $suite->id,
                'room_number'    => $f . '08B',
                'floor'          => $floor,
                'room_sub_type'  => 'suite_b',
                'linked_room_id' => $suiteA8->id,
                'status'         => 'available',
            ]);
            $suiteA8->update(['linked_room_id' => $suiteB8->id]);
        }
    }
}
