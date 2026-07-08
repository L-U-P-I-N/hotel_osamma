<?php
namespace App\Http\Controllers;

use App\Models\Floor;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class FloorController extends Controller
{
    public function index()
    {
        $floors = Floor::orderBy('floor_number')->with('rooms')->get();

        // معرّفات الغرف المرتبطة بحجوزات نشطة (حالية أو مستقبلية) — تشمل الغرفة
        // الرئيسية والمرتبطة (جناح كامل) — لتحديد الطوابق التي بها نزلاء/حجوزات.
        $reservedRoomIds = Reservation::where('status', 'checked_in')
            ->get(['room_id', 'linked_room_id'])
            ->flatMap(fn($r) => [$r->room_id, $r->linked_room_id])
            ->filter()
            ->unique();

        $floors->each(function ($floor) use ($reservedRoomIds) {
            $floor->used_doors = $floor->rooms
                ->pluck('room_number')
                ->map(fn($rn) => preg_replace('/[^0-9]/', '', $rn))
                ->unique()
                ->count();

            // الطابق "في الصيانة" عندما تكون كل غرفه في حالة صيانة
            $floor->in_maintenance = $floor->rooms->isNotEmpty()
                && $floor->rooms->every(fn($r) => $r->status === 'maintenance');

            // نزلاء/حجوزات تمنع وضع الطابق في الصيانة: غرفة مشغولة الآن أو محجوزة
            $floor->blocking_rooms = $floor->rooms
                ->filter(fn($r) => $r->status === 'occupied' || $reservedRoomIds->contains($r->id))
                ->pluck('room_number')
                ->values();
            $floor->has_guest = $floor->blocking_rooms->isNotEmpty();
        });

        return view('floors.index', compact('floors'));
    }

    /**
     * وضع كل غرف الطابق في الصيانة — يُمنع إن كان بأي غرفة نزيل حالي أو حجز
     * (حالي أو مستقبلي)، فيجب إخراج النزلاء/إلغاء الحجوزات أولاً.
     */
    public function maintenance(Floor $floor)
    {
        $rooms = Room::where('floor', $floor->floor_number)->get();
        if ($rooms->isEmpty()) {
            return back()->with('error', 'لا توجد غرف في هذا الطابق لوضعها في الصيانة');
        }

        $roomIds  = $rooms->pluck('id');
        $reserved = Reservation::where('status', 'checked_in')
            ->where(fn($q) => $q->whereIn('room_id', $roomIds)->orWhereIn('linked_room_id', $roomIds))
            ->exists();
        $occupied = $rooms->contains(fn($r) => $r->status === 'occupied');

        if ($occupied || $reserved) {
            return back()->with('error',
                'لا يمكن وضع الطابق ' . $floor->floor_number . ' في الصيانة لوجود نزلاء حاليين أو حجوزات على بعض غرفه. '
                . 'يجب تسجيل خروج النزلاء أو إلغاء الحجوزات أولاً.');
        }

        $changed = 0;
        foreach ($rooms as $room) {
            if ($room->status !== 'maintenance') {
                $old = ['status' => $room->status];
                $room->update(['status' => 'maintenance']);
                AuditLogService::log('update', $room, $old, ['status' => 'maintenance', 'floor_maintenance' => $floor->floor_number], auth()->user());
                $changed++;
            }
        }

        return back()->with('success', "تم وضع الطابق {$floor->floor_number} بالكامل في الصيانة ({$changed} غرفة)");
    }

    /**
     * إنهاء صيانة الطابق — إعادة كل غرفه إلى "متاحة" (عدا أي غرفة مشغولة، احترازياً).
     */
    public function endMaintenance(Floor $floor)
    {
        $rooms = Room::where('floor', $floor->floor_number)
            ->where('status', '!=', 'occupied')
            ->get();

        $changed = 0;
        foreach ($rooms as $room) {
            if ($room->status !== 'available') {
                $old = ['status' => $room->status];
                $room->update(['status' => 'available']);
                AuditLogService::log('update', $room, $old, ['status' => 'available', 'floor_maintenance_end' => $floor->floor_number], auth()->user());
                $changed++;
            }
        }

        return back()->with('success', "تم إنهاء صيانة الطابق {$floor->floor_number} وإعادة غرفه إلى متاحة ({$changed} غرفة)");
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'floor_number' => 'required|integer|min:1|max:50|unique:floors,floor_number',
            'door_count'   => 'required|integer|min:1|max:100',
            'name'         => 'nullable|string|max:100',
        ], [
            'floor_number.required' => 'رقم الطابق مطلوب',
            'floor_number.integer'  => 'رقم الطابق يجب أن يكون رقماً صحيحاً',
            'floor_number.min'      => 'رقم الطابق يجب أن يكون 1 على الأقل',
            'floor_number.max'      => 'رقم الطابق لا يتجاوز 50',
            'floor_number.unique'   => 'هذا الطابق موجود مسبقاً',
            'door_count.required'   => 'عدد الأبواب مطلوب',
            'door_count.integer'    => 'عدد الأبواب يجب أن يكون رقماً صحيحاً',
            'door_count.min'        => 'عدد الأبواب يجب أن يكون 1 على الأقل',
            'door_count.max'        => 'عدد الأبواب لا يتجاوز 100',
        ]);

        Floor::create($validated);

        return redirect()->route('floors.index')->with('success', 'تم إضافة الطابق بنجاح');
    }

    public function update(Request $request, Floor $floor)
    {
        $validated = $request->validate([
            'floor_number' => 'required|integer|min:1|max:50|unique:floors,floor_number,' . $floor->id,
            'door_count'   => 'required|integer|min:1|max:100',
            'name'         => 'nullable|string|max:100',
        ], [
            'floor_number.required' => 'رقم الطابق مطلوب',
            'floor_number.unique'   => 'هذا الطابق موجود مسبقاً',
            'door_count.required'   => 'عدد الأبواب مطلوب',
            'door_count.min'        => 'عدد الأبواب يجب أن يكون 1 على الأقل',
        ]);

        $roomCount = Room::where('floor', $floor->floor_number)->count();
        if ($roomCount > 0 && $validated['door_count'] < $roomCount) {
            return back()->withErrors(['door_count' => 'لا يمكن تقليل عدد الأبواب إلى ' . $validated['door_count'] . ' لوجود ' . $roomCount . ' غرف مسجلة في هذا الطابق']);
        }

        $floor->update($validated);

        return redirect()->route('floors.index')->with('success', 'تم تحديث الطابق بنجاح');
    }

    public function destroy(Floor $floor)
    {
        $roomCount = Room::where('floor', $floor->floor_number)->count();
        if ($roomCount > 0) {
            return redirect()->route('floors.index')->with('error', 'لا يمكن حذف الطابق ' . $floor->floor_number . ' لوجود ' . $roomCount . ' غرف مسجلة فيه');
        }

        $floor->delete();

        return redirect()->route('floors.index')->with('success', 'تم حذف الطابق بنجاح');
    }

    public function availableRoomNumbers(Floor $floor)
    {
        $usedNumbers = Room::where('floor', $floor->floor_number)->pluck('room_number')->toArray();
        $allNumbers  = $floor->validRoomNumbers();

        // Derive base numbers from suite variants (e.g. '601A' → '601', '608B' → '608')
        // so that base number slots occupied by suites are also excluded.
        $usedBaseNumbers = array_unique(array_map(fn($rn) => rtrim($rn, 'ABab'), $usedNumbers));

        $available = array_values(array_filter(
            $allNumbers,
            fn($num) => !in_array($num, $usedNumbers, true) && !in_array($num, $usedBaseNumbers, true)
        ));

        return response()->json([
            'floor'     => $floor->floor_number,
            'range'     => $floor->range,
            'available' => $available,
            'all'       => $allNumbers,
        ]);
    }
}
