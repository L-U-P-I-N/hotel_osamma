<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Convert all non-target statuses before altering enum
        DB::table('reservations')->where('status', 'confirmed')->update(['status' => 'checked_in']);
        DB::table('reservations')->where('status', 'cancelled')->update(['status' => 'checked_out']);
        DB::table('reservations')->whereNotIn('status', ['checked_in', 'checked_out'])->update(['status' => 'checked_in']);

        DB::table('rooms')->where('status', 'reserved')->update(['status' => 'available']);
        DB::table('rooms')->whereNotIn('status', ['available', 'occupied', 'under_inspection', 'maintenance'])->update(['status' => 'available']);

        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('checked_in','checked_out') NOT NULL DEFAULT 'checked_in'");
        DB::statement("ALTER TABLE rooms MODIFY COLUMN status ENUM('available','occupied','under_inspection','maintenance') NOT NULL DEFAULT 'available'");
    }

    public function down(): void
    {
        // Restore old enums
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('confirmed','checked_in','checked_out','cancelled') NOT NULL DEFAULT 'confirmed'");
        DB::statement("ALTER TABLE rooms MODIFY COLUMN status ENUM('available','reserved','occupied','under_inspection','maintenance') NOT NULL DEFAULT 'available'");
    }
};
