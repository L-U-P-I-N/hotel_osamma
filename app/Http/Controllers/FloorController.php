<?php
namespace App\Http\Controllers;

use App\Models\Floor;
use App\Models\Room;
use Illuminate\Http\Request;

class FloorController extends Controller
{
    public function index()
    {
        $floors = Floor::withCount('rooms')->orderBy('floor_number')->get();
        return view('floors.index', compact('floors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'floor_number' => 'required|integer|unique:floors,floor_number',
            'door_count'   => 'required|integer|min:1|max:50',
            'name'         => 'nullable|string|max:100',
        ], [
            'floor_number.required' => 'رقم الطابق مطلوب',
            'floor_number.integer'  => 'رقم الطابق يجب أن يكون رقماً صحيحاً',
            'floor_number.unique'   => 'رقم الطابق موجود مسبقاً',
            'door_count.required'   => 'عدد الأبواب مطلوب',
            'door_count.integer'    => 'عدد الأبواب يجب أن يكون رقماً صحيحاً',
            'door_count.min'        => 'عدد الأبواب يجب أن يكون 1 على الأقل',
            'door_count.max'        => 'عدد الأبواب لا يتجاوز 50',
            'name.max'              => 'اسم الطابق لا يتجاوز 100 حرف',
        ]);

        Floor::create($validated);

        return redirect()->back()->with('success', 'تم إضافة الطابق بنجاح');
    }

    public function update(Request $request, Floor $floor)
    {
        $validated = $request->validate([
            'floor_number' => 'required|integer|unique:floors,floor_number,' . $floor->id,
            'door_count'   => 'required|integer|min:1|max:50',
            'name'         => 'nullable|string|max:100',
        ], [
            'floor_number.required' => 'رقم الطابق مطلوب',
            'floor_number.integer'  => 'رقم الطابق يجب أن يكون رقماً صحيحاً',
            'floor_number.unique'   => 'رقم الطابق موجود مسبقاً في طابق آخر',
            'door_count.required'   => 'عدد الأبواب مطلوب',
            'door_count.integer'    => 'عدد الأبواب يجب أن يكون رقماً صحيحاً',
            'door_count.min'        => 'عدد الأبواب يجب أن يكون 1 على الأقل',
            'door_count.max'        => 'عدد الأبواب لا يتجاوز 50',
            'name.max'              => 'اسم الطابق لا يتجاوز 100 حرف',
        ]);

        $floor->update($validated);

        return redirect()->back()->with('success', 'تم تحديث بيانات الطابق بنجاح');
    }

    public function destroy(Floor $floor)
    {
        $roomCount = Room::where('floor', $floor->floor_number)->count();
        if ($roomCount > 0) {
            return redirect()->back()->with('error', 'لا يمكن حذف الطابق ' . $floor->floor_number . ' لوجود ' . $roomCount . ' غرفة مرتبطة به');
        }

        $floor->delete();

        return redirect()->back()->with('success', 'تم حذف الطابق بنجاح');
    }

    public function availableRoomNumbers(Floor $floor)
    {
        $allNumbers = $floor->roomNumbers();
        $usedNumbers = Room::where('floor', $floor->floor_number)->pluck('room_number')->toArray();
        $available = array_values(array_filter($allNumbers, fn($n) => !in_array($n, $usedNumbers)));
        return response()->json($available);
    }
}
