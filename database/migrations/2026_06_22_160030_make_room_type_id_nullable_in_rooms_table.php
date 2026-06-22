<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Raw SQL is more reliable than ->change() for FK + nullable modifications
        DB::statement('ALTER TABLE rooms MODIFY COLUMN room_type_id BIGINT UNSIGNED NULL');

        // Drop and re-add FK with SET NULL on delete
        try {
            DB::statement('ALTER TABLE rooms DROP FOREIGN KEY rooms_room_type_id_foreign');
        } catch (\Exception $e) {
            // FK may already be dropped or named differently — safe to continue
        }

        DB::statement(
            'ALTER TABLE rooms ADD CONSTRAINT rooms_room_type_id_foreign
             FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE SET NULL'
        );
    }

    public function down(): void
    {
        // Restore NOT NULL — only safe if no nulls exist
        try {
            DB::statement('ALTER TABLE rooms DROP FOREIGN KEY rooms_room_type_id_foreign');
        } catch (\Exception $e) {}

        DB::statement('ALTER TABLE rooms MODIFY COLUMN room_type_id BIGINT UNSIGNED NOT NULL');
        DB::statement(
            'ALTER TABLE rooms ADD CONSTRAINT rooms_room_type_id_foreign
             FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE CASCADE'
        );
    }
};
