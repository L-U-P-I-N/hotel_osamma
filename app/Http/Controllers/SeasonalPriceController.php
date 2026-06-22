<?php
namespace App\Http\Controllers;

use App\Models\RoomType;
use App\Models\SeasonalPrice;
use Illuminate\Http\Request;

class SeasonalPriceController extends Controller
{
    public function index()
    {
        $seasons   = SeasonalPrice::with(['roomType', 'createdBy'])->orderBy('from_date', 'desc')->get();
        $roomTypes = RoomType::orderBy('name')->get();
        return view('seasonal-prices.index', compact('seasons', 'roomTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_type_id' => 'nullable|exists:room_types,id',
            'name'         => 'required|string|max:100',
            'from_date'    => 'required|date',
            'to_date'      => 'required|date|after_or_equal:from_date',
            'price_mode'   => 'required|in:fixed,multiplier',
            'price_yer'    => 'required_if:price_mode,fixed|nullable|numeric|min:1',
            'multiplier'   => 'required_if:price_mode,multiplier|nullable|numeric|min:0.1|max:10',
            'notes'        => 'nullable|string|max:500',
        ], [
            'name.required'       => 'اسم الموسم مطلوب',
            'from_date.required'  => 'تاريخ البداية مطلوب',
            'to_date.required'    => 'تاريخ النهاية مطلوب',
            'price_yer.required_if' => 'السعر الثابت مطلوب',
            'multiplier.required_if' => 'معامل الضرب مطلوب',
        ]);

        $validated['created_by'] = auth()->id();
        SeasonalPrice::create($validated);

        return redirect()->route('seasonal-prices.index')->with('success', 'تم إضافة السعر الموسمي بنجاح');
    }

    public function edit(SeasonalPrice $seasonalPrice)
    {
        $roomTypes = RoomType::orderBy('name')->get();
        return view('seasonal-prices.edit', compact('seasonalPrice', 'roomTypes'));
    }

    public function update(Request $request, SeasonalPrice $seasonalPrice)
    {
        $validated = $request->validate([
            'room_type_id' => 'nullable|exists:room_types,id',
            'name'         => 'required|string|max:100',
            'from_date'    => 'required|date',
            'to_date'      => 'required|date|after_or_equal:from_date',
            'price_mode'   => 'required|in:fixed,multiplier',
            'price_yer'    => 'required_if:price_mode,fixed|nullable|numeric|min:1',
            'multiplier'   => 'required_if:price_mode,multiplier|nullable|numeric|min:0.1|max:10',
            'notes'        => 'nullable|string|max:500',
        ]);

        $seasonalPrice->update($validated);
        return redirect()->route('seasonal-prices.index')->with('success', 'تم تحديث السعر الموسمي');
    }

    public function destroy(SeasonalPrice $seasonalPrice)
    {
        $seasonalPrice->delete();
        return back()->with('success', 'تم حذف السعر الموسمي');
    }
}
