<?php
namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomType;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

/**
 * إعدادات التسعير — للمدير (صلاحية pricing.manage).
 * هنا يُحدَّد نطاق سعر الليلة لكل نوع، وهو النطاق الذي يُفرض على الموظف
 * في كل مسار إدخال سعر: حجز جديد، تعديل، تجديد، إعادة تسعير، مقاطع الإقامة.
 */
class PricingController extends Controller
{
    public function index()
    {
        $roomTypes = RoomType::withCount('rooms')->orderBy('name')->get();

        // الأنواع التي لها أقسام أجنحة هي وحدها التي تُعرض لها خانة "الجناح كاملاً"
        $suiteTypeIds = Room::whereIn('room_sub_type', ['suite_a', 'suite_b'])
            ->distinct()
            ->pluck('room_type_id')
            ->all();

        return view('pricing.index', compact('roomTypes', 'suiteTypeIds'));
    }

    public function updateRoomType(Request $request, RoomType $roomType)
    {
        $hasSuiteSections = $roomType->rooms()
            ->whereIn('room_sub_type', ['suite_a', 'suite_b'])
            ->exists();

        $validated = $request->validate([
            'base_price'      => 'required|numeric|min:1|max:99999999',
            'min_price'       => 'required|numeric|min:1|max:99999999',
            'max_price'       => 'required|numeric|min:1|max:99999999|gte:min_price',
            'suite_min_price' => ($hasSuiteSections ? 'required' : 'nullable') . '|numeric|min:1|max:99999999',
            'suite_max_price' => ($hasSuiteSections ? 'required' : 'nullable') . '|numeric|min:1|max:99999999|gte:suite_min_price',
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
            'suite_min_price.required' => 'أقل سعر للجناح كاملاً مطلوب',
            'suite_min_price.numeric'  => 'أقل سعر للجناح يجب أن يكون رقماً',
            'suite_min_price.min'      => 'أقل سعر للجناح يجب أن يكون أكبر من صفر',
            'suite_max_price.required' => 'أعلى سعر للجناح كاملاً مطلوب',
            'suite_max_price.numeric'  => 'أعلى سعر للجناح يجب أن يكون رقماً',
            'suite_max_price.gte'      => 'أعلى سعر للجناح يجب أن يكون مساوياً أو أكبر من أقل سعر للجناح',
        ]);

        // بلا أقسام أجنحة لا معنى لتخزين نطاق جناح
        if (!$hasSuiteSections) {
            unset($validated['suite_min_price'], $validated['suite_max_price']);
        }

        // السعر الأساسي هو المقترح تلقائياً على الموظف، فلا معنى لوقوعه خارج النطاق
        if ($validated['base_price'] < $validated['min_price'] || $validated['base_price'] > $validated['max_price']) {
            return back()->withInput()->withErrors([
                'base_price' => 'السعر الأساسي يجب أن يقع بين أقل سعر وأعلى سعر',
            ]);
        }

        // تحذير مبكر بدل مفاجأة عند الحجز: غرف سعرها الخاص خارج النطاق الجديد
        $outOfRange = $roomType->rooms()
            ->whereNotNull('price_yer')
            ->where('price_yer', '>', 0)
            ->where(fn($q) => $q->where('price_yer', '<', $validated['min_price'])
                                ->orWhere('price_yer', '>', $validated['max_price']))
            ->pluck('room_number');

        $old = $roomType->only(['base_price', 'min_price', 'max_price', 'suite_min_price', 'suite_max_price']);
        $roomType->update($validated);

        AuditLogService::log('update', $roomType, $old, $roomType->fresh()->toArray(), auth()->user());

        $message = 'تم تحديث تسعير "' . $roomType->name . '" بنجاح';
        if ($outOfRange->isNotEmpty()) {
            $message .= ' — تنبيه: أسعار الغرف ' . $outOfRange->implode('، ')
                        . ' خارج النطاق الجديد وستُرفض عند الحجز حتى تُعدَّل.';
        }

        return back()->with('success', $message);
    }
}
