<?php

namespace App\Console\Commands;

use App\Models\Floor;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * إعادة ضبط النظام للتسليم:
 *  - حذف كل البيانات التشغيلية والمالية (حجوزات، نزلاء، مدفوعات، مصروفات،
 *    ورديات، تقارير وإيرادات، رواتب/حضور/إجازات...).
 *  - الإبقاء على حسابات المستخدمين والصلاحيات وإعدادات الفندق، وكذلك سجلّات
 *    الموظفين والموردين (بيانات أساسية — والمستخدمون مرتبطون بالموظفين إلزامياً
 *    في قاعدة البيانات فلا يمكن حذفهم دون كسر الحسابات).
 *  - إعادة بناء الطوابق والغرف: 6 طوابق × 8 أبواب، البابان 1 و 8 جناحان (قسم
 *    A + قسم B)، الباب 6 غرفة زوجية، والباقي غرف عادية. الأسعار: العادية والقسم
 *    45,000 والجناح الكامل 85,000.
 */
class ResetForHandover extends Command
{
    protected $signature = 'hotel:reset-for-handover {--force : تخطّي رسالة التأكيد}';

    protected $description = 'حذف كل البيانات التشغيلية والمالية وإعادة بناء الطوابق والغرف للتسليم — يُبقي حسابات المستخدمين والموظفين';

    public function handle(): int
    {
        $hotel = Hotel::first();
        if (!$hotel) {
            $this->error('لا يوجد فندق مُعرّف في النظام — أوقفت العملية.');
            return 1;
        }

        $this->warn('سيُحذف: الحجوزات، النزلاء، المدفوعات، الرسوم، الاستردادات، المصروفات، الورديات، '
            . 'المسحوبات، الميزانيات، الأسعار الموسمية، الرواتب والحضور والإجازات، وسجلّات التدقيق '
            . '(كل التقارير والإيرادات).');
        $this->line('سيبقى: حسابات المستخدمين والصلاحيات وإعدادات الفندق، وأسماء الموظفين والموردين '
            . '(بيانات أساسية فقط، بلا أي حركات مالية).');
        $this->line('سيُعاد بناء: 6 طوابق × 8 أبواب (البابان 1 و 8 جناحان، الباب 6 زوجية، والباقي عادية).');

        if (!$this->option('force')
            && !$this->confirm('متأكد من المتابعة؟ هذا الإجراء لا يمكن التراجع عنه.')) {
            $this->info('تم الإلغاء.');
            return 0;
        }

        // ── جداول البيانات التشغيلية والمالية المطلوب حذفها ──
        // (نُبقي employees و suppliers كبيانات أساسية — المستخدمون مرتبطون
        //  بالموظفين إلزامياً، وحذفهم يكسر الحسابات.)
        $wipe = [
            'audit_logs', 'extra_charges', 'payments', 'companions', 'reservations', 'guests',
            'refunds', 'room_inspections', 'inspection_images',
            'cash_withdrawals', 'cash_settlements', 'expenses', 'budgets', 'seasonal_prices',
            'salaries', 'attendances', 'leaves', 'shifts',
        ];

        $this->fkOff();
        try {
            foreach ($wipe as $table) {
                try {
                    DB::table($table)->delete();
                    $this->line("  ✓ حُذف {$table}");
                } catch (\Throwable $e) {
                    $this->warn("  ✗ {$table}: " . $e->getMessage());
                }
            }

            // حذف الغرف والطوابق تمهيداً لإعادة البناء
            DB::table('rooms')->delete();
            DB::table('floors')->delete();
            $this->line('  ✓ حُذفت الغرف والطوابق القديمة');
        } finally {
            $this->fkOn();
        }

        // ── إعادة البناء ──
        $this->ensureRoomTypes($hotel->id);
        $this->buildFloorsAndRooms($hotel->id);

        $this->newLine();
        $this->info('تمّت إعادة الضبط بنجاح.');
        $this->info('الطوابق: ' . Floor::count() . ' — الغرف: ' . Room::count()
            . ' — المستخدمون (محفوظون): ' . DB::table('users')->count());

        return 0;
    }

    /** ضمان وجود أنواع الغرف المستخدمة بالسعر الصحيح. */
    private function ensureRoomTypes(int $hotelId): void
    {
        // [الاسم، السعر الأساسي، السعة]
        $types = [
            ['غرفة عادية', 45000, 2],
            ['جناح',       45000, 4],
            ['شقة',        0,     4],
            ['صالة',       0,     10],
        ];

        foreach ($types as [$name, $price, $cap]) {
            $existing = RoomType::withTrashed()->where('hotel_id', $hotelId)->where('name', $name)->first();
            if ($existing) {
                if (method_exists($existing, 'trashed') && $existing->trashed()) {
                    $existing->restore();
                }
                $existing->update(['base_price' => $price, 'max_capacity' => $cap]);
            } else {
                RoomType::create([
                    'hotel_id'     => $hotelId,
                    'name'         => $name,
                    'base_price'   => $price,
                    'max_capacity' => $cap,
                ]);
            }
        }
    }

    /** بناء 6 طوابق × 8 أبواب وفق المواصفات المطلوبة. */
    private function buildFloorsAndRooms(int $hotelId): void
    {
        $regular = RoomType::where('hotel_id', $hotelId)->where('name', 'غرفة عادية')->first();
        $suite   = RoomType::where('hotel_id', $hotelId)->where('name', 'جناح')->first();

        $floorNames = [1 => 'الأول', 2 => 'الثاني', 3 => 'الثالث', 4 => 'الرابع', 5 => 'الخامس', 6 => 'السادس'];

        for ($floor = 1; $floor <= 6; $floor++) {
            Floor::create([
                'floor_number' => $floor,
                'door_count'   => 8,
                'name'         => 'الطابق ' . $floorNames[$floor],
            ]);

            $f = (string) $floor;

            for ($door = 1; $door <= 8; $door++) {
                $num = $f . str_pad((string) $door, 2, '0', STR_PAD_LEFT);

                if ($door === 1 || $door === 8) {
                    // جناح: قسم A + قسم B — القسم 45,000، والجناح كاملاً 85,000
                    $a = Room::create([
                        'hotel_id'        => $hotelId,
                        'room_type_id'    => $suite->id,
                        'room_number'     => $num . 'A',
                        'floor'           => $floor,
                        'room_sub_type'   => 'suite_a',
                        'price_yer'       => 45000,
                        'suite_price_yer' => 85000,
                        'beds_count'      => 2,
                        'status'          => 'available',
                    ]);
                    $b = Room::create([
                        'hotel_id'        => $hotelId,
                        'room_type_id'    => $suite->id,
                        'room_number'     => $num . 'B',
                        'floor'           => $floor,
                        'room_sub_type'   => 'suite_b',
                        'price_yer'       => 45000,
                        'suite_price_yer' => 85000,
                        'beds_count'      => 2,
                        'linked_room_id'  => $a->id,
                        'status'          => 'available',
                    ]);
                    $a->update(['linked_room_id' => $b->id]);
                } elseif ($door === 6) {
                    // غرفة زوجية — 45,000
                    Room::create([
                        'hotel_id'      => $hotelId,
                        'room_type_id'  => $regular->id,
                        'room_number'   => $num,
                        'floor'         => $floor,
                        'room_sub_type' => 'double',
                        'price_yer'     => 45000,
                        'beds_count'    => 2,
                        'status'        => 'available',
                    ]);
                } else {
                    // غرفة عادية — 45,000
                    Room::create([
                        'hotel_id'     => $hotelId,
                        'room_type_id' => $regular->id,
                        'room_number'  => $num,
                        'floor'        => $floor,
                        'price_yer'    => 45000,
                        'beds_count'   => 1,
                        'status'       => 'available',
                    ]);
                }
            }
        }
    }

    private function fkOff(): void
    {
        DB::getDriverName() === 'sqlite'
            ? DB::statement('PRAGMA foreign_keys = OFF')
            : DB::statement('SET FOREIGN_KEY_CHECKS=0');
    }

    private function fkOn(): void
    {
        DB::getDriverName() === 'sqlite'
            ? DB::statement('PRAGMA foreign_keys = ON')
            : DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
