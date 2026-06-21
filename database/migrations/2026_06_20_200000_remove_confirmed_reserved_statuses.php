<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Clear all transactional data (keep rooms, floors, users, employees)
        DB::table('audit_logs')->truncate();
        DB::table('extra_charges')->truncate();
        DB::table('payments')->truncate();
        DB::table('companions')->truncate();
        DB::table('reservations')->truncate();
        DB::table('guests')->truncate();
        DB::table('cash_withdrawals')->truncate();
        DB::table('cash_settlements')->truncate();
        DB::table('expenses')->truncate();
        DB::table('salaries')->truncate();
        DB::table('shifts')->truncate();
        DB::table('room_inspections')->truncate();
        DB::table('inspection_images')->truncate();

        // Reset all rooms to available
        DB::table('rooms')->update(['status' => 'available']);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Now safely alter enums
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
