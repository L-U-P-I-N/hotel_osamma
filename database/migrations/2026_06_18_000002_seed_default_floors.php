<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $floors = [
            ['floor_number' => 1, 'door_count' => 1,  'name' => 'الطابق الأول'],
            ['floor_number' => 2, 'door_count' => 8,  'name' => 'الطابق الثاني'],
            ['floor_number' => 3, 'door_count' => 8,  'name' => 'الطابق الثالث'],
            ['floor_number' => 4, 'door_count' => 8,  'name' => 'الطابق الرابع'],
            ['floor_number' => 5, 'door_count' => 8,  'name' => 'الطابق الخامس'],
            ['floor_number' => 6, 'door_count' => 8,  'name' => 'الطابق السادس'],
        ];

        foreach ($floors as $floor) {
            DB::table('floors')->insertOrIgnore(array_merge($floor, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        DB::table('floors')->whereIn('floor_number', [1, 2, 3, 4, 5, 6])->delete();
    }
};
