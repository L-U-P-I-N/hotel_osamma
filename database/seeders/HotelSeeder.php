<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hotel;

class HotelSeeder extends Seeder
{
    public function run(): void
    {
        Hotel::create([
            'name' => 'فندق أسامة',
            'address' => 'صنعاء، الجمهورية اليمنية',
            'phone' => '+967 1 234567',
            'email' => 'info@hotel-osama.com',
        ]);
    }
}
