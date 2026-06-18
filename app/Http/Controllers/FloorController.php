<?php
namespace App\Http\Controllers;

use App\Models\Floor;
use App\Models\Room;
use Illuminate\Http\Request;

class FloorController extends Controller
{
    public function index()
    {
        $floors = Floor::orderBy('floor_number')->withCount('rooms')->get();
        return view('floors.index', compact('floors'));
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
        $available   = array_values(array_diff($allNumbers, $usedNumbers));

        return response()->json([
            'floor'     => $floor->floor_number,
            'range'     => $floor->range,
            'available' => $available,
            'all'       => $allNumbers,
        ]);
    }
}
