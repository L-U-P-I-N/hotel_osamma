<?php
namespace App\Http\Controllers;

use App\Models\Floor;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $query = Room::with(['roomType', 'hotel']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->whereHas('roomType', fn($q) => $q->where('name', $request->type));
        }
        if ($request->filled('sub_type')) {
            $query->where('room_sub_type', $request->sub_type);
        }
        if ($request->filled('floor')) {
            $query->where('floor', $request->floor);
        }

        $rooms = $query->orderBy('floor')->orderBy('room_number')->get();
        $roomTypes = RoomType::all();
        $floors = Room::distinct()->pluck('floor')->sort();

        return view('rooms.index', compact('rooms', 'roomTypes', 'floors'));
    }

    public function create()
    {
        $roomTypes = RoomType::all();
        $canPrice  = auth()->user()->can('room.price.edit');
        $floors    = Floor::orderBy('floor_number')->get();
        return view('rooms.create', compact('roomTypes', 'canPrice', 'floors'));
    }

    public function store(Request $request)
    {
        $request->merge(['room_number' => Room::normalizeDigits($request->input('room_number', ''))]);

        $validated = $request->validate([
            'room_number'    => 'required|string|max:10|unique:rooms,room_number',
            'floor'          => 'required|integer|min:1|max:50',
            'room_type_id'   => 'required|exists:room_types,id',
            'room_sub_type'  => 'nullable|in:regular,double,suite,suite_a,suite_b,hall,apartment',
            'beds_count'     => 'nullable|integer|min:1|max:20',
            'price_yer'      => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string|max:500',
        ], [
            'room_number.required'  => 'رقم الغرفة مطلوب',
            'room_number.max'       => 'رقم الغرفة لا يتجاوز 10 أحرف',
            'room_number.unique'    => 'رقم الغرفة موجود مسبقاً',
            'floor.required'        => 'رقم الطابق مطلوب',
            'floor.integer'         => 'رقم الطابق يجب أن يكون رقماً صحيحاً',
            'floor.min'             => 'رقم الطابق يجب أن يكون 1 على الأقل',
            'floor.max'             => 'رقم الطابق لا يتجاوز 50',
            'room_type_id.required' => 'نوع الغرفة مطلوب',
            'room_type_id.exists'   => 'نوع الغرفة المحدد غير موجود',
            'room_sub_type.in'      => 'تصنيف الغرفة غير صالح',
            'price_yer.numeric'     => 'السعر بالريال اليمني يجب أن يكون رقماً',
            'notes.max'             => 'الملاحظات لا تتجاوز 500 حرف',
        ]);

        $floor = Floor::where('floor_number', $validated['floor'])->first();
        if ($floor && !$floor->isValidRoomNumber($validated['room_number'])) {
            return back()->withInput()->withErrors([
                'room_number' => 'رقم الغرفة ' . $validated['room_number'] . ' لا ينتمي للطابق ' . $validated['floor'] . ' الذي يحتوي على ' . $floor->door_count . ' أبواب فقط (من ' . ($floor->floor_number * 100 + 1) . ' إلى ' . ($floor->floor_number * 100 + $floor->door_count) . ')',
            ]);
        }

        $hotel    = Hotel::first();
        $subType  = $validated['room_sub_type'] ?? 'regular';
        $isSuite  = $subType === 'suite';

        $baseAttributes = [
            'hotel_id'     => $hotel->id,
            'room_type_id' => $validated['room_type_id'],
            'floor'        => $validated['floor'],
            'beds_count'   => $validated['beds_count'] ?? 1,
            'status'       => 'available',
            'notes'        => $validated['notes'] ?? null,
        ];

        if (auth()->user()->can('room.price.edit')) {
            $baseAttributes['price_yer'] = $validated['price_yer'] ?? null;
        }

        if ($isSuite) {
            $numA = $validated['room_number'] . 'A';
            $numB = $validated['room_number'] . 'B';

            if (Room::whereIn('room_number', [$numA, $numB])->exists()) {
                return back()->withInput()->withErrors(['room_number' => 'رقم الجناح ' . $validated['room_number'] . ' موجود مسبقاً']);
            }

            $roomA = Room::create(array_merge($baseAttributes, ['room_number' => $numA, 'room_sub_type' => 'suite_a']));
            $roomB = Room::create(array_merge($baseAttributes, ['room_number' => $numB, 'room_sub_type' => 'suite_b', 'linked_room_id' => $roomA->id]));
            $roomA->update(['linked_room_id' => $roomB->id]);

            AuditLogService::log('create', $roomA, [], $roomA->toArray(), auth()->user());
            AuditLogService::log('create', $roomB, [], $roomB->toArray(), auth()->user());

            return redirect()->route('rooms.index')->with('success', 'تم إنشاء الجناح بنجاح: ' . $numA . ' و ' . $numB);
        }

        $room = Room::create(array_merge($baseAttributes, [
            'room_number'   => $validated['room_number'],
            'room_sub_type' => $subType,
        ]));

        AuditLogService::log('create', $room, [], $room->toArray(), auth()->user());

        return redirect()->route('rooms.index')->with('success', 'تم إضافة الغرفة ' . $room->room_number . ' بنجاح');
    }

    public function edit(Room $room)
    {
        $roomTypes = RoomType::all();
        $canPrice  = auth()->user()->can('room.price.edit');
        $floors    = Floor::orderBy('floor_number')->get();
        return view('rooms.edit', compact('room', 'roomTypes', 'canPrice', 'floors'));
    }

    public function update(Request $request, Room $room)
    {
        $request->merge(['room_number' => Room::normalizeDigits($request->input('room_number', ''))]);

        $validated = $request->validate([
            'room_number'    => 'required|string|max:10|unique:rooms,room_number,' . $room->id,
            'floor'          => 'required|integer|min:1|max:50',
            'room_type_id'   => 'required|exists:room_types,id',
            'room_sub_type'  => 'nullable|in:regular,double,suite,suite_a,suite_b,hall,apartment',
            'beds_count'     => 'nullable|integer|min:1|max:20',
            'price_yer'      => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string|max:500',
        ], [
            'room_number.required'  => 'رقم الغرفة مطلوب',
            'room_number.max'       => 'رقم الغرفة لا يتجاوز 10 أحرف',
            'room_number.unique'    => 'رقم الغرفة موجود مسبقاً في غرفة أخرى',
            'floor.required'        => 'رقم الطابق مطلوب',
            'floor.integer'         => 'رقم الطابق يجب أن يكون رقماً صحيحاً',
            'floor.min'             => 'رقم الطابق يجب أن يكون 1 على الأقل',
            'floor.max'             => 'رقم الطابق لا يتجاوز 50',
            'room_type_id.required' => 'نوع الغرفة مطلوب',
            'room_type_id.exists'   => 'نوع الغرفة المحدد غير موجود',
            'room_sub_type.in'      => 'تصنيف الغرفة غير صالح',
            'price_yer.numeric'     => 'السعر بالريال اليمني يجب أن يكون رقماً',
            'notes.max'             => 'الملاحظات لا تتجاوز 500 حرف',
        ]);

        $floor = Floor::where('floor_number', $validated['floor'])->first();
        if ($floor && !$floor->isValidRoomNumber($validated['room_number'])) {
            return back()->withInput()->withErrors([
                'room_number' => 'رقم الغرفة ' . $validated['room_number'] . ' لا ينتمي للطابق ' . $validated['floor'] . ' الذي يحتوي على ' . $floor->door_count . ' أبواب فقط (من ' . ($floor->floor_number * 100 + 1) . ' إلى ' . ($floor->floor_number * 100 + $floor->door_count) . ')',
            ]);
        }

        $old = $room->toArray();
        $attributes = [
            'room_number'   => $validated['room_number'],
            'floor'         => $validated['floor'],
            'beds_count'    => $validated['beds_count'] ?? $room->beds_count,
            'room_type_id'  => $validated['room_type_id'],
            'room_sub_type' => $validated['room_sub_type'] ?? $room->room_sub_type,
            'notes'         => $validated['notes'] ?? null,
        ];

        if (auth()->user()->can('room.price.edit')) {
            $attributes['price_yer'] = $validated['price_yer'] ?? null;
        }

        $room->update($attributes);
        AuditLogService::log('update', $room, $old, $room->fresh()->toArray(), auth()->user());

        return redirect()->route('rooms.index')->with('success', 'تم تحديث بيانات الغرفة بنجاح');
    }

    public function destroy(Room $room)
    {
        if ($room->reservations()->where('status', 'checked_in')->exists()) {
            return back()->with('error', 'لا يمكن حذف الغرفة ' . $room->room_number . ' لوجود حجوزات نشطة عليها');
        }

        AuditLogService::log('delete', $room, $room->toArray(), [], auth()->user());
        $room->delete();

        return redirect()->route('rooms.index')->with('success', 'تم حذف الغرفة بنجاح');
    }

    public function available()
    {
        $rooms = Room::with('roomType')->available()->orderBy('floor')->orderBy('room_number')->get();
        return response()->json($rooms);
    }

    public function updateStatus(Request $request, Room $room)
    {
        $request->validate([
            'status' => 'required|in:available,under_inspection,maintenance',
        ], [
            'status.required' => 'الحالة مطلوبة',
            'status.in'       => 'لا يمكن تعيين هذه الحالة يدوياً — مشغولة ومحجوزة تتغيران تلقائياً عبر الحجوزات فقط',
        ]);

        $old = ['status' => $room->status];
        $room->update(['status' => $request->status, 'notes' => $request->notes]);
        AuditLogService::log('update', $room, $old, ['status' => $request->status], auth()->user());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'room' => $room->fresh()]);
        }
        return redirect()->route('rooms.index')->with('success', 'تم تحديث حالة الغرفة بنجاح');
    }
}
