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

        $types = [
            // min_price / max_price = النطاق الذي يستطيع الموظف الحجز ضمنه؛ يعدّله المدير من /pricing
            ['name' => 'غرفة عادية', 'base_price' => 8000,  'min_price' => 7000,  'max_price' => 10000, 'max_capacity' => 2,  'description' => 'غرفة عادية مريحة'],
            ['name' => 'جناح',       'base_price' => 15000, 'min_price' => 13000, 'max_price' => 20000, 'max_capacity' => 4,  'description' => 'جناح فاخر — يُحجز منفرداً أو مع الجناح المقابل'],
            ['name' => 'شقة',        'base_price' => 20000, 'min_price' => 18000, 'max_price' => 26000, 'max_capacity' => 6,  'description' => 'شقة كاملة — تُحجز دائماً كوحدة واحدة'],
            ['name' => 'صالة',       'base_price' => 30000, 'min_price' => 25000, 'max_price' => 45000, 'max_capacity' => 50, 'description' => 'صالة اجتماعات وأفراح'],
        ];

        foreach ($types as $type) {
            RoomType::firstOrCreate(
                ['hotel_id' => $hotel->id, 'name' => $type['name']],
                $type + ['hotel_id' => $hotel->id]
            );
        }
    }
}
