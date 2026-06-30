<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * يُصلح الربط بين قسمي الجناح (A و B) للأجنحة التي فُقد فيها linked_room_id،
     * بمطابقة رقم الغرفة (مثال: 301A ↔ 301B). بدون هذا الربط لا يظهر خيار حجز
     * "الجناح كامل A+B" ولا يشمل الحجز القسم الآخر.
     */
    public function up(): void
    {
        $rooms = DB::table('rooms')
            ->whereIn('room_sub_type', ['suite_a', 'suite_b'])
            ->whereNull('deleted_at')
            ->get(['id', 'room_number', 'room_sub_type', 'linked_room_id']);

        $byNumber = [];
        foreach ($rooms as $r) {
            $byNumber[$r->room_number] = $r;
        }

        foreach ($rooms as $r) {
            if ($r->linked_room_id) {
                continue;
            }
            $base = rtrim($r->room_number, 'ABab');
            $pairNumber = $base . ($r->room_sub_type === 'suite_a' ? 'B' : 'A');

            if (isset($byNumber[$pairNumber])) {
                DB::table('rooms')->where('id', $r->id)->update([
                    'linked_room_id' => $byNumber[$pairNumber]->id,
                ]);
            }
        }
    }

    public function down(): void
    {
        // لا تراجع — الربط بيانات تصحيحية
    }
};
