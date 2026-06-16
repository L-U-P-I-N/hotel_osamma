<?php
namespace App\Http\Controllers;

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
        return view('rooms.create', compact('roomTypes'));
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'room_number'    => 'required|string|max:10|unique:rooms,room_number',
            'floor'          => 'required|integer|min:1|max:30',
            'room_type_id'   => 'required|exists:room_types,id',
            'room_sub_type'  => 'nullable|in:regular,double,suite_a,suite_b,hall,apartment',
            'notes'          => 'nullable|string|max:500',
        ], [
            'room_number.required'  => 'رقم الغرفة مطلوب',
            'room_number.max'       => 'رقم الغرفة لا يتجاوز 10 أحرف',
            'room_number.unique'    => 'رقم الغرفة موجود مسبقاً',
            'floor.required'        => 'رقم الطابق مطلوب',
            'floor.integer'         => 'رقم الطابق يجب أن يكون رقماً صحيحاً',
            'floor.min'             => 'رقم الطابق يجب أن يكون 1 على الأقل',
            'floor.max'             => 'رقم الطابق لا يتجاوز 30',
            'room_type_id.required' => 'نوع الغرفة مطلوب',
            'room_type_id.exists'   => 'نوع الغرفة المحدد غير موجود',
            'room_sub_type.in'      => 'تصنيف الغرفة غير صالح',
            'notes.max'             => 'الملاحظات لا تتجاوز 500 حرف',
        ]);

        $hotel = Hotel::first();

        $room = Room::create([
            'hotel_id'      => $hotel->id,
            'room_type_id'  => $validated['room_type_id'],
            'room_number'   => $validated['room_number'],
            'floor'         => $validated['floor'],
            'room_sub_type' => $validated['room_sub_type'] ?? 'regular',
            'status'        => 'available',
            'notes'         => $validated['notes'] ?? null,
        ]);

        AuditLogService::log('create', $room, [], $room->toArray(), auth()->user());

        return redirect()->route('rooms.index')->with('success', 'تم إضافة الغرفة ' . $room->room_number . ' بنجاح');
    }

    public function edit(Room $room)
    {
        $roomTypes = RoomType::all();
        return view('rooms.edit', compact('room', 'roomTypes'));
    }

    public function update(Request $request, Room $room)
    {

        $validated = $request->validate([
            'room_number'    => 'required|string|max:10|unique:rooms,room_number,' . $room->id,
            'floor'          => 'required|integer|min:1|max:30',
            'room_type_id'   => 'required|exists:room_types,id',
            'room_sub_type'  => 'nullable|in:regular,double,suite_a,suite_b,hall,apartment',
            'notes'          => 'nullable|string|max:500',
        ], [
            'room_number.required'  => 'رقم الغرفة مطلوب',
            'room_number.max'       => 'رقم الغرفة لا يتجاوز 10 أحرف',
            'room_number.unique'    => 'رقم الغرفة موجود مسبقاً في غرفة أخرى',
            'floor.required'        => 'رقم الطابق مطلوب',
            'floor.integer'         => 'رقم الطابق يجب أن يكون رقماً صحيحاً',
            'floor.min'             => 'رقم الطابق يجب أن يكون 1 على الأقل',
            'floor.max'             => 'رقم الطابق لا يتجاوز 30',
            'room_type_id.required' => 'نوع الغرفة مطلوب',
            'room_type_id.exists'   => 'نوع الغرفة المحدد غير موجود',
            'room_sub_type.in'      => 'تصنيف الغرفة غير صالح',
            'notes.max'             => 'الملاحظات لا تتجاوز 500 حرف',
        ]);

        $old = $room->toArray();
        $room->update([
            'room_number'   => $validated['room_number'],
            'floor'         => $validated['floor'],
            'room_type_id'  => $validated['room_type_id'],
            'room_sub_type' => $validated['room_sub_type'] ?? $room->room_sub_type,
            'notes'         => $validated['notes'] ?? null,
        ]);
        AuditLogService::log('update', $room, $old, $room->fresh()->toArray(), auth()->user());

        return redirect()->route('rooms.index')->with('success', 'تم تحديث بيانات الغرفة بنجاح');
    }

    public function destroy(Room $room)
    {

        if ($room->reservations()->whereIn('status', ['confirmed', 'checked_in'])->exists()) {
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
            'status' => 'required|in:available,reserved,occupied,under_inspection,maintenance',
        ], [
            'status.required' => 'الحالة مطلوبة',
            'status.in'       => 'الحالة المحددة غير صالحة',
        ]);

        $old = ['status' => $room->status];
        $room->update(['status' => $request->status, 'notes' => $request->notes]);
        AuditLogService::log('update', $room, $old, ['status' => $request->status], auth()->user());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'room' => $room->fresh()]);
        }
        return back()->with('success', 'تم تحديث حالة الغرفة بنجاح');
    }
}
