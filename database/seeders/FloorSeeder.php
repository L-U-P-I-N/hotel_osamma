<?php
namespace Database\Seeders;

use App\Models\Floor;
use Illuminate\Database\Seeder;

/**
 * طوابق الفندق. idempotent عبر updateOrCreate على رقم الطابق — كان truncate
 * يمسح الجدول في كل تشغيل، فلا يصلح للتنفيذ التلقائي عند النشر.
 */
class FloorSeeder extends Seeder
{
    public function run(): void
    {
        $floors = [
            ['floor_number' => 1, 'door_count' => 1, 'name' => 'الطابق الأول'],
            ['floor_number' => 2, 'door_count' => 8, 'name' => 'الطابق الثاني'],
            ['floor_number' => 3, 'door_count' => 8, 'name' => 'الطابق الثالث'],
            ['floor_number' => 4, 'door_count' => 8, 'name' => 'الطابق الرابع'],
            ['floor_number' => 5, 'door_count' => 8, 'name' => 'الطابق الخامس'],
            ['floor_number' => 6, 'door_count' => 8, 'name' => 'الطابق السادس'],
        ];

        foreach ($floors as $floor) {
            Floor::updateOrCreate(
                ['floor_number' => $floor['floor_number']],
                ['door_count' => $floor['door_count'], 'name' => $floor['name']]
            );
        }
    }
}
