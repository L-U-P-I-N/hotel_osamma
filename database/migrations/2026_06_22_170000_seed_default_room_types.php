<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $hotel = DB::table('hotels')->first();
        if (!$hotel) {
            return;
        }

        $types = ['غرفة عادية', 'جناح', 'شقة', 'صالة'];

        foreach ($types as $name) {
            $exists = DB::table('room_types')
                ->where('hotel_id', $hotel->id)
                ->where('name', $name)
                ->whereNull('deleted_at')
                ->exists();

            if (!$exists) {
                DB::table('room_types')->insert([
                    'hotel_id'     => $hotel->id,
                    'name'         => $name,
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
        // Intentionally not deleting seeded data
    }
};
