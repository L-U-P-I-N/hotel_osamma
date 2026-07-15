<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearTransactionalData extends Command
{
    protected $signature   = 'hotel:clear-data {--force : Skip confirmation prompt}';
    protected $description = 'Delete all transactional data — keeps users, rooms, floors only';

    public function handle(): int
    {
        if (!$this->option('force')) {
            if (!$this->confirm('سيتم حذف جميع البيانات (حجوزات، نزلاء، مدفوعات، مصروفات...) مع الإبقاء على المستخدمين والغرف والطوابق. متأكد؟')) {
                $this->info('تم الإلغاء.');
                return 0;
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $tables = [
            'audit_logs',
            'extra_charges',
            'reservation_segments',
            'payments',
            'companions',
            'reservations',
            'guests',
            'cash_withdrawals',
            'cash_settlements',
            'expenses',
            'salaries',
            'shifts',
            'room_inspections',
            'inspection_images',
        ];

        foreach ($tables as $table) {
            try {
                DB::table($table)->truncate();
                $this->line("  ✓ {$table}");
            } catch (\Exception $e) {
                $this->warn("  ✗ {$table}: " . $e->getMessage());
            }
        }

        // Reset all rooms to available
        DB::table('rooms')->update(['status' => 'available']);
        $this->line('  ✓ rooms → status = available');

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->info('تم حذف جميع البيانات بنجاح.');
        return 0;
    }
}
