<?php
namespace App\Http\Controllers;

use App\Models\Currency;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CurrencyController extends Controller
{
    public function index()
    {
        $currencies = Currency::orderBy('is_primary', 'desc')->orderBy('code')->get();
        return view('currencies.index', compact('currencies'));
    }

    public function update(Request $request, Currency $currency)
    {
        $request->validate([
            'exchange_rate_to_yer' => 'required|numeric|min:0.01|max:9999999',
            'is_active'            => 'boolean',
        ], [
            'exchange_rate_to_yer.required' => 'سعر الصرف مطلوب',
            'exchange_rate_to_yer.numeric'  => 'سعر الصرف يجب أن يكون رقماً',
            'exchange_rate_to_yer.min'      => 'سعر الصرف يجب أن يكون أكبر من صفر',
        ]);

        // لا يمكن تعطيل العملة الأساسية
        if ($currency->is_primary && !$request->boolean('is_active', true)) {
            return back()->withErrors(['is_active' => 'لا يمكن تعطيل العملة الأساسية (الريال اليمني)']);
        }

        $old = $currency->toArray();
        $currency->update([
            'exchange_rate_to_yer' => $request->exchange_rate_to_yer,
            'is_active'            => $currency->is_primary ? true : $request->boolean('is_active', $currency->is_active),
        ]);

        Cache::forget('active_currencies');
        AuditLogService::log('update', $currency, $old, $currency->fresh()->toArray(), auth()->user());

        return back()->with('success', 'تم تحديث سعر صرف ' . $currency->name . ' بنجاح');
    }
}
