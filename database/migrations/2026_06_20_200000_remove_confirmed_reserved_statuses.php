<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

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

        Schema::enableForeignKeyConstraints();

        // SQLite has no native ENUM/MODIFY COLUMN — column already stores these
        // values as plain strings, so no DDL change is needed on that driver.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('checked_in','checked_out') NOT NULL DEFAULT 'checked_in'");
            DB::statement("ALTER TABLE rooms MODIFY COLUMN status ENUM('available','occupied','under_inspection','maintenance') NOT NULL DEFAULT 'available'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            // Restore old enums
            DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('confirmed','checked_in','checked_out','cancelled') NOT NULL DEFAULT 'confirmed'");
            DB::statement("ALTER TABLE rooms MODIFY COLUMN status ENUM('available','reserved','occupied','under_inspection','maintenance') NOT NULL DEFAULT 'available'");
        }
    }
};
