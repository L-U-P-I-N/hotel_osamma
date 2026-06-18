<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $hotels = DB::table('hotels')->pluck('id');

        foreach ($hotels as $hotelId) {
            $exists = DB::table('room_types')
                ->where('hotel_id', $hotelId)
                ->where('name', 'زوجية')
                ->exists();

            if (!$exists) {
                DB::table('room_types')->insert([
                    'hotel_id'     => $hotelId,
                    'name'         => 'زوجية',
                    'base_price'   => 0,
                    'max_capacity' => 2,
                    'description'  => null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('room_types')->where('name', 'زوجية')->delete();
    }
};
