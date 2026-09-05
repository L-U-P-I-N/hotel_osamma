<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hotel;

class HotelSeeder extends Seeder
{
    public function run(): void
    {
        // firstOrCreate لا create: الاستدعاء المتكرر كان يضيف فندقاً جديداً
        // في كل مرة، وكل التقارير تقرأ Hotel::first().
        Hotel::firstOrCreate(['name' => 'فندق السعودي'], [
            'address' => 'صنعاء، الجمهورية اليمنية',
            'phone' => '+967 1 234567',
            'email' => 'info@hotel-saudi.com',
        ]);
    }
}
