<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hotel;
use App\Models\RoomType;

class RoomTypeSeeder extends Seeder
{
    public function run(): void
    {
        $hotel = Hotel::first();

        RoomType::create([
            'hotel_id' => $hotel->id,
            'name' => 'single',
            'base_price' => 5000.00,
            'max_capacity' => 1,
            'description' => 'غرفة مفردة مريحة',
        ]);

        RoomType::create([
            'hotel_id' => $hotel->id,
            'name' => 'double',
            'base_price' => 8000.00,
            'max_capacity' => 2,
            'description' => 'غرفة مزدوجة فسيحة',
        ]);

        RoomType::create([
            'hotel_id' => $hotel->id,
            'name' => 'suite',
            'base_price' => 15000.00,
            'max_capacity' => 4,
            'description' => 'جناح فاخر مع صالة',
        ]);
    }
}
