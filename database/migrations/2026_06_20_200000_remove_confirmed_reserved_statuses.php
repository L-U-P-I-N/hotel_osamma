<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Convert existing 'confirmed' reservations to 'checked_in'
        DB::table('reservations')->where('status', 'confirmed')->update(['status' => 'checked_in']);

        // 2. Convert existing 'reserved' rooms to 'available'
        DB::table('rooms')->where('status', 'reserved')->update(['status' => 'available']);

        // 3. Change reservations.status enum (remove 'confirmed' and 'cancelled')
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('checked_in','checked_out') NOT NULL DEFAULT 'checked_in'");

        // 4. Change rooms.status enum (remove 'reserved')
        DB::statement("ALTER TABLE rooms MODIFY COLUMN status ENUM('available','occupied','under_inspection','maintenance') NOT NULL DEFAULT 'available'");
    }

    public function down(): void
    {
        // Restore old enums
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('confirmed','checked_in','checked_out','cancelled') NOT NULL DEFAULT 'confirmed'");
        DB::statement("ALTER TABLE rooms MODIFY COLUMN status ENUM('available','reserved','occupied','under_inspection','maintenance') NOT NULL DEFAULT 'available'");
    }
};
