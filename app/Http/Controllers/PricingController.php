<?php
namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\RoomType;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

/**
 * إعدادات التسعير — للمدير فقط (صلاحية pricing.manage).
 * هنا يُحدَّد نطاق السعر الليلي لكل نوع (غرفة/جناح/شقة/صالة) وسقف الخصم.
 */
class PricingController extends Controller
{
    public function index()
    {
        $hotel     = Hotel::firstOrFail();
        $roomTypes = RoomType::withCount('rooms')->orderBy('name')->get();

        return view('pricing.index', compact('hotel', 'roomTypes'));
    }

    public function updateRoomType(Request $request, RoomType $roomType)
    {
        $validated = $request->validate([
            'base_price' => 'required|numeric|min:1|max:99999999',
            'min_price'  => 'required|numeric|min:1|max:99999999',
            'max_price'  => 'required|numeric|min:1|max:99999999|gte:min_price',
        ], [
            'base_price.required' => 'السعر الأساسي مطلوب',
            'base_price.numeric'  => 'السعر الأساسي يجب أن يكون رقماً',
            'base_price.min'      => 'السعر الأساسي يجب أن يكون أكبر من صفر',
            'min_price.required'  => 'أقل سعر مطلوب',
            'min_price.numeric'   => 'أقل سعر يجب أن يكون رقماً',
            'min_price.min'       => 'أقل سعر يجب أن يكون أكبر من صفر',
            'max_price.required'  => 'أعلى سعر مطلوب',
            'max_price.numeric'   => 'أعلى سعر يجب أن يكون رقماً',
            'max_price.gte'       => 'أعلى سعر يجب أن يكون مساوياً أو أكبر من أقل سعر',
        ]);

        // السعر الأساسي هو ما يُقترح على الموظف تلقائياً، فيجب أن يقع داخل النطاق
        if ($validated['base_price'] < $validated['min_price'] || $validated['base_price'] > $validated['max_price']) {
            return back()->withInput()->withErrors([
                'base_price' => 'السعر الأساسي يجب أن يقع بين أقل سعر وأعلى سعر',
            ]);
        }

        $old = $roomType->only(['base_price', 'min_price', 'max_price']);
        $roomType->update($validated);

        AuditLogService::log('update', $roomType, $old, $roomType->fresh()->toArray(), auth()->user());

        return back()->with('success', 'تم تحديث تسعير "' . $roomType->name . '" بنجاح');
    }

    public function updateDiscountCeiling(Request $request)
    {
        $validated = $request->validate([
            'max_discount_percent' => 'required|numeric|min:0|max:100',
        ], [
            'max_discount_percent.required' => 'نسبة سقف الخصم مطلوبة',
            'max_discount_percent.numeric'  => 'نسبة سقف الخصم يجب أن تكون رقماً',
            'max_discount_percent.min'      => 'نسبة سقف الخصم لا تقل عن 0',
            'max_discount_percent.max'      => 'نسبة سقف الخصم لا تتجاوز 100',
        ]);

        $hotel = Hotel::firstOrFail();
        $old   = $hotel->only(['max_discount_percent']);
        $hotel->update($validated);

        AuditLogService::log('update', $hotel, $old, $hotel->fresh()->toArray(), auth()->user());

        return back()->with('success', 'تم تحديث سقف الخصم إلى ' . (float) $validated['max_discount_percent'] . '%');
    }
}
