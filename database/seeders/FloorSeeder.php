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

        $floorData = DB::table('rooms')
            ->select('floor')
            ->whereNotNull('floor')
            ->groupBy('floor')
            ->orderBy('floor')
            ->get();

        foreach ($floorData as $row) {
            $floorNum = (int)$row->floor;

            $roomNumbers = DB::table('rooms')
                ->where('floor', $floorNum)
                ->pluck('room_number')
                ->toArray();

            $maxDoor = 0;
            foreach ($roomNumbers as $rn) {
                $numeric = preg_replace('/[^0-9]/', '', $rn);
                if ($numeric && strlen($numeric) > 1) {
                    $prefix = (int)substr($numeric, 0, strlen((string)$floorNum));
                    if ($prefix === $floorNum) {
                        $door = (int)substr($numeric, strlen((string)$floorNum));
                        if ($door > $maxDoor) {
                            $maxDoor = $door;
                        }
                    }
                }
            }

            $doorCount = $maxDoor > 0 ? $maxDoor : count($roomNumbers);
            if ($doorCount < 1) {
                $doorCount = 10;
            }

            Floor::create([
                'floor_number' => $floorNum,
                'door_count'   => $doorCount,
                'name'         => null,
            ]);
        }
    }
}
