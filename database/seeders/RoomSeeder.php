<?php
namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * غرف الفندق الافتراضية. idempotent عبر updateOrCreate على رقم الغرفة.
 *
 * كان يحذف كل غرف الفندق ثم يعيد إنشاءها، فتضيع حالات الغرف وأسعارها
 * المضبوطة يدوياً وتُكسر إشارات الحجوزات إليها — لا يصلح للتنفيذ التلقائي.
 *
 * ما يُنشئه هنا هو الهيكل فقط (رقم الغرفة، الطابق، النوع، الارتباط)؛
 * الحالة والسعر لا يُلمسان إن كانت الغرفة موجودة مسبقاً.
 */
class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $hotel = Hotel::first();

        if (!$hotel) {
            return;
        }

        $regular = RoomType::where('hotel_id', $hotel->id)->where('name', 'غرفة عادية')->first();
        $suite   = RoomType::where('hotel_id', $hotel->id)->where('name', 'جناح')->first();
        $hall    = RoomType::where('hotel_id', $hotel->id)->where('name', 'صالة')->first();

        if (!$regular || !$suite || !$hall) {
            return; // أنواع الغرف لم تُبذر بعد
        }

        DB::transaction(function () use ($hotel, $regular, $suite, $hall) {
            // الطابق الأول: صالة واحدة
            $this->ensureRoom($hotel->id, $hall->id, '101', 1, 'hall');

            // الطوابق 2-6: 8 أبواب — الباب 1 و8 جناحان (A+B)، والأبواب 2-7 غرف عادية
            for ($floor = 2; $floor <= 6; $floor++) {
                $f = (string) $floor;

                foreach (['01', '08'] as $door) {
                    $a = $this->ensureRoom($hotel->id, $suite->id, $f . $door . 'A', $floor, 'suite_a');
                    $b = $this->ensureRoom($hotel->id, $suite->id, $f . $door . 'B', $floor, 'suite_b');

                    // الربط يُضبط في الاتجاهين، ولا يُعاد كتابته إن كان صحيحاً
                    if ($a->linked_room_id !== $b->id) {
                        $a->update(['linked_room_id' => $b->id]);
                    }
                    if ($b->linked_room_id !== $a->id) {
                        $b->update(['linked_room_id' => $a->id]);
                    }
                }

                for ($door = 2; $door <= 7; $door++) {
                    $this->ensureRoom(
                        $hotel->id,
                        $regular->id,
                        $f . str_pad((string) $door, 2, '0', STR_PAD_LEFT),
                        $floor,
                        'regular'
                    );
                }
            }
        });
    }

    /**
     * تُنشئ الغرفة إن غابت، وتصحّح هيكلها إن وُجدت — دون المساس بحالتها
     * أو سعرها، فهما بيانات تشغيل يضبطها الفندق لا البذرة.
     */
    private function ensureRoom(int $hotelId, int $typeId, string $number, int $floor, string $subType): Room
    {
        $room = Room::where('room_number', $number)->first();

        if ($room === null) {
            return Room::create([
                'hotel_id'      => $hotelId,
                'room_type_id'  => $typeId,
                'room_number'   => $number,
                'floor'         => $floor,
                'room_sub_type' => $subType,
                'status'        => 'available',
            ]);
        }

        $room->update([
            'hotel_id'      => $hotelId,
            'room_type_id'  => $room->room_type_id ?: $typeId,
            'floor'         => $floor,
            'room_sub_type' => $subType,
        ]);

        return $room;
    }
}
