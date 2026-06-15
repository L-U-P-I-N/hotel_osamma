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

        // Clear existing rooms safely (bypass FK checks per driver)
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
        $apt     = RoomType::where('hotel_id', $hotel->id)->where('name', 'شقة')->first();
        $hall    = RoomType::where('hotel_id', $hotel->id)->where('name', 'صالة')->first();

        // Floor 1: hall only
        $hallRoom = Room::create([
            'hotel_id'      => $hotel->id,
            'room_type_id'  => $hall->id,
            'room_number'   => '101',
            'floor'         => 1,
            'room_sub_type' => 'hall',
            'status'        => 'available',
        ]);

        // Floors 2-6: suite A/B pair + 2 regular + apartment pair + 2 regular
        for ($floor = 2; $floor <= 6; $floor++) {
            $f = (string)$floor;

            // Suite A (e.g. 201A) — linked to suite B
            $suiteA = Room::create([
                'hotel_id'      => $hotel->id,
                'room_type_id'  => $suite->id,
                'room_number'   => $f . '01A',
                'floor'         => $floor,
                'room_sub_type' => 'suite_a',
                'status'        => 'available',
            ]);

            // Suite B (e.g. 201B) — linked to suite A
            $suiteB = Room::create([
                'hotel_id'      => $hotel->id,
                'room_type_id'  => $suite->id,
                'room_number'   => $f . '01B',
                'floor'         => $floor,
                'room_sub_type' => 'suite_b',
                'linked_room_id'=> $suiteA->id,
                'status'        => 'available',
            ]);
            $suiteA->update(['linked_room_id' => $suiteB->id]);

            // Regular rooms 202 and 203
            Room::create([
                'hotel_id'     => $hotel->id,
                'room_type_id' => $regular->id,
                'room_number'  => $f . '02',
                'floor'        => $floor,
                'status'       => 'available',
            ]);
            Room::create([
                'hotel_id'     => $hotel->id,
                'room_type_id' => $regular->id,
                'room_number'  => $f . '03',
                'floor'        => $floor,
                'status'       => 'available',
            ]);

            // Apartment pair 204 + 205 (always linked)
            $apt1 = Room::create([
                'hotel_id'        => $hotel->id,
                'room_type_id'    => $apt->id,
                'room_number'     => $f . '04',
                'floor'           => $floor,
                'room_sub_type'   => 'apartment',
                'is_always_linked'=> true,
                'status'          => 'available',
            ]);
            $apt2 = Room::create([
                'hotel_id'        => $hotel->id,
                'room_type_id'    => $apt->id,
                'room_number'     => $f . '05',
                'floor'           => $floor,
                'room_sub_type'   => 'apartment',
                'is_always_linked'=> true,
                'linked_room_id'  => $apt1->id,
                'status'          => 'available',
            ]);
            $apt1->update(['linked_room_id' => $apt2->id]);

            // Regular rooms 206 and 207
            Room::create([
                'hotel_id'     => $hotel->id,
                'room_type_id' => $regular->id,
                'room_number'  => $f . '06',
                'floor'        => $floor,
                'status'       => 'available',
            ]);
            Room::create([
                'hotel_id'     => $hotel->id,
                'room_type_id' => $regular->id,
                'room_number'  => $f . '07',
                'floor'        => $floor,
                'status'       => 'available',
            ]);
        }
    }
}
