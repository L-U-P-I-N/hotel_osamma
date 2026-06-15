<?php
namespace App\Http\Controllers;

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

    public function available()
    {
        $rooms = Room::with('roomType')->available()->orderBy('floor')->orderBy('room_number')->get();
        return response()->json($rooms);
    }

    public function updateStatus(Request $request, Room $room)
    {
        $request->validate([
            'status' => 'required|in:available,reserved,occupied,under_inspection,maintenance',
        ]);

        $old = ['status' => $room->status];
        $room->update(['status' => $request->status, 'notes' => $request->notes]);
        AuditLogService::log('update', $room, $old, ['status' => $request->status]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'room' => $room->fresh()]);
        }
        return back()->with('success', 'تم تحديث حالة الغرفة بنجاح');
    }
}
