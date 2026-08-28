<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            HotelSeeder::class,
            RolesSeeder::class,
            AccountsSeeder::class,
            UserSeeder::class,
            RoomTypeSeeder::class,
            RoomSeeder::class,
            FloorSeeder::class,
            TestDataSeeder::class,
            HrExpenseSeeder::class,
        ]);
    }
}
