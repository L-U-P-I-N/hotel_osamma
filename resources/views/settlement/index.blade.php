@extends('layouts.app')
@section('title', 'التسوية النقدية')
@section('page-title', 'التسوية النقدية اليومية')

@php $currencyLabels = ['YER' => 'ريال يمني', 'SAR' => 'ريال سعودي', 'USD' => 'دولار أمريكي']; @endphp

@section('content')
<div x-data="settlementPage()" x-init="init()" dir="rtl">

<!-- Settlement Header -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-lg font-bold text-gray-800">حساب يوم {{ $settlement->shift_date->format('d/m/Y') }}</h2>
                @if($settlement->status === 'locked')
                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">مُقفل ✓</span>
                @else
                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">مفتوح</span>
                @endif
            </div>
            <p class="text-sm text-gray-500 mt-1">الموظف: {{ $settlement->user->name }}</p>
            @if($settlement->status === 'locked')
            <p class="text-xs text-gray-400 mt-0.5">أُقفل بواسطة {{ $settlement->lockedBy?->name }} في {{ $settlement->locked_at?->format('d/m/Y H:i') }}</p>
            @endif
        </div>
        @if($settlement->status === 'open')
        @can('settlement.lock')
        <button onclick="document.getElementById('lockModal').classList.remove('hidden')"
                class="px-5 py-2.5 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700 transition">
            إقفال الحساب
        </button>
        @endcan
        @endif
    </div>
</div>

<!-- Per-Currency Summary Cards -->
@php $hasAnyCurrencyData = collect($perCurrency['by_currency'])->where('has_data', true)->isNotEmpty(); @endphp
@if($hasAnyCurrencyData)
<div class="mb-5">
    <h3 class="font-semibold text-gray-700 mb-3 text-sm">ملخص العملات</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($perCurrency['by_currency'] as $cur => $data)
        @if($data['has_data'])
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-bold text-gray-800 text-sm">{{ $currencyLabels[$cur] }}</h4>
                <span class="text-xs font-mono px-2 py-0.5 rounded" style="background:#e8f0f7; color:#0F4C75;">{{ $cur }}</span>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">المستلم</span>
                    <span class="font-semibold text-green-700">{{ number_format($data['received'], 2) }}</span>
                </div>
                @if($data['expenses'] > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">المصروفات</span>
                    <span class="font-semibold text-red-600">{{ number_format($data['expenses'], 2) }}</span>
                </div>
                @endif
                @if($data['exchange_out'] > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">صرف عملة (خارج)</span>
                    <span class="font-semibold text-orange-600">{{ number_format($data['exchange_out'], 2) }}</span>
                </div>
                @endif
                <div class="border-t border-gray-100 pt-2 flex justify-between text-sm">
                    <span class="font-semibold text-gray-700">الصافي</span>
                    <span class="font-bold text-lg" style="color:#0F4C75;">{{ number_format($data['net'], 2) }}</span>
                </div>
            </div>
        </div>
        @endif
        @endforeach
    </div>
</div>
@endif

<!-- Payment Details Table -->
@if($perCurrency['payment_details']->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-5">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">تفاصيل الإيرادات النقدية</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الغرفة</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المبلغ</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">العملة</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($perCurrency['payment_details'] as $p)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600">{{ \Carbon\Carbon::parse($p->payment_date)->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $p->reservation?->room?->room_number ?? '-' }}</td>
                    <td class="px-4 py-3 font-bold text-green-700">{{ number_format($p->amount, 2) }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded text-xs font-medium" style="background:#e8f0f7; color:#0F4C75;">
                            {{ $currencyLabels[$p->currency] ?? $p->currency }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Cash Expenses (auto-synced from expense module) -->
@php $cashExpenses = $settlement->withdrawals->where('withdrawal_type', 'expense'); @endphp
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-5">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
            <h3 class="font-semibold text-gray-700">المصروفات النقدية من الصندوق</h3>
            <p class="text-xs text-gray-400 mt-0.5">تُسجَّل من وحدة المصروفات وتُخصم تلقائياً</p>
        </div>
        @if($settlement->status === 'open')
        @can('expenses.create')
        <a href="{{ route('expenses.create') }}"
           class="flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            تسجيل مصروف
        </a>
        @endcan
        @endif
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الفئة</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المبلغ</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المستلم</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">البيان</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($cashExpenses as $w)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600">{{ \Carbon\Carbon::parse($w->withdrawal_date)->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3">
                        @if($w->expense)
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            {{ \App\Models\Expense::categoryLabel($w->expense->category) }}
                        </span>
                        @else
                        <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-bold text-red-600">{{ number_format($w->amount, 2) }} {{ $currencyLabels[$w->currency] ?? $w->currency }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $w->withdrawn_by_name }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $w->notes ?: '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400 text-sm">لا توجد مصروفات نقدية — سجّل مصروفاً من وحدة المصروفات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Currency Exchange Section -->
@php $exchanges = $perCurrency['exchanges']; @endphp
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-5">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-700">صرف العملات</h3>
        @if($settlement->status === 'open')
        @can('settlement.manage')
        <button @click="withdrawalModal=true"
                class="flex items-center gap-2 px-4 py-2 bg-orange-500 text-white rounded-lg text-sm hover:bg-orange-600 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            إضافة صرف عملة
        </button>
        @endcan
        @endif
    </div>
    @if($exchanges->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المُعطى</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المستلم</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المستلم من</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">ملاحظات</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($exchanges as $ex)
                <tr class="hover:bg-orange-50/30">
                    <td class="px-4 py-3 text-gray-600">{{ \Carbon\Carbon::parse($ex->withdrawal_date)->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3 font-bold text-red-600">
                        {{ number_format($ex->amount, 2) }} {{ $currencyLabels[$ex->currency] ?? $ex->currency }}
                    </td>
                    <td class="px-4 py-3 font-bold text-green-700">
                        @if($ex->exchange_to_amount)
                        {{ number_format($ex->exchange_to_amount, 2) }} {{ $currencyLabels[$ex->exchange_to_currency] ?? $ex->exchange_to_currency }}
                        @else —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ $ex->withdrawn_by_name }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $ex->notes ?: '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="px-4 py-6 text-center text-gray-400 text-sm">لا توجد عمليات صرف عملة اليوم</div>
    @endif
</div>

<!-- Signatures -->
@can('settlement.manage')
@if($settlement->status === 'open')
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5">
    <h3 class="font-semibold text-gray-700 mb-4">التوقيعات</h3>
    <form method="POST" action="{{ route('settlement.signatures') }}">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2 text-center">توقيع الموظف</label>
                <canvas id="employeeSigCanvas" class="border-2 border-dashed border-gray-300 rounded-xl w-full touch-none cursor-crosshair bg-gray-50" style="height:128px;"></canvas>
                <div class="flex gap-2 mt-2">
                    <button type="button" onclick="clearCanvas('employeeSigCanvas', 'employee_signature')" class="text-xs text-gray-500 hover:text-red-500">مسح</button>
                </div>
                <input type="hidden" name="employee_signature" id="employee_signature">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2 text-center">توقيع الإدارة</label>
                <canvas id="adminSigCanvas" class="border-2 border-dashed border-gray-300 rounded-xl w-full touch-none cursor-crosshair bg-gray-50" style="height:128px;"></canvas>
                <div class="flex gap-2 mt-2">
                    <button type="button" onclick="clearCanvas('adminSigCanvas', 'admin_signature')" class="text-xs text-gray-500 hover:text-red-500">مسح</button>
                </div>
                <input type="hidden" name="admin_signature" id="admin_signature">
            </div>
        </div>
        <div class="mt-4">
            <button type="submit" onclick="return captureSignatures()" class="px-5 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">
                حفظ التوقيعات
            </button>
        </div>
    </form>
</div>
@else
@if($settlement->employee_signature)
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5">
    <h3 class="font-semibold text-gray-700 mb-4">التوقيعات المحفوظة</h3>
    <div class="grid grid-cols-2 gap-4">
        <div class="text-center">
            <p class="text-sm text-gray-500 mb-2">توقيع الموظف</p>
            <img src="{{ $settlement->employee_signature }}" class="border rounded-lg h-24 mx-auto">
        </div>
        <div class="text-center">
            <p class="text-sm text-gray-500 mb-2">توقيع الإدارة</p>
            <img src="{{ $settlement->admin_signature }}" class="border rounded-lg h-24 mx-auto">
        </div>
    </div>
</div>
@endif
@endif
@endcan

<!-- Currency Exchange Modal -->
<div x-show="withdrawalModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="withdrawalModal=false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-screen overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-800">صرف عملة</h3>
            <button @click="withdrawalModal=false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('settlement.withdrawal') }}" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="withdrawal_type" value="currency_exchange">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">المبلغ المُعطى *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">عملته</label>
                    <select name="currency" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none">
                        <option value="YER">ريال يمني</option>
                        <option value="SAR">ريال سعودي</option>
                        <option value="USD">دولار أمريكي</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 border border-orange-200 rounded-lg p-3 bg-orange-50">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">المبلغ المستلم *</label>
                    <input type="number" name="exchange_to_amount" step="0.01" min="0.01" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none bg-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">عملته</label>
                    <select name="exchange_to_currency" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none bg-white">
                        <option value="SAR">ريال سعودي</option>
                        <option value="USD">دولار أمريكي</option>
                        <option value="YER">ريال يمني</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">التاريخ والوقت *</label>
                <input type="datetime-local" name="withdrawal_date" required value="{{ now()->format('Y-m-d\TH:i') }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">استلم بواسطة *</label>
                <input type="text" name="withdrawn_by_name" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none" placeholder="اسم المستلم">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">سلّم بواسطة *</label>
                <input type="text" name="handed_by_name" required value="{{ auth()->user()->name }}" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">ملاحظات</label>
                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none resize-none"></textarea>
            </div>
            <button type="submit" class="w-full text-white py-2.5 rounded-lg font-semibold transition text-sm bg-orange-500 hover:bg-orange-600">
                حفظ صرف العملة
            </button>
        </form>
    </div>
</div>

<!-- Lock Confirmation Modal -->
<div id="lockModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>
        <h3 class="font-bold text-gray-800 mb-2">تأكيد إقفال الحساب</h3>
        <p class="text-gray-500 text-sm mb-5">لا يمكن التراجع عن هذا الإجراء بعد الإقفال</p>
        <div class="flex gap-3">
            <form method="POST" action="{{ route('settlement.lock') }}" class="flex-1">
                @csrf
                <button type="submit" class="w-full bg-red-600 text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-red-700 transition">تأكيد الإقفال</button>
            </form>
            <button onclick="document.getElementById('lockModal').classList.add('hidden')"
                    class="flex-1 border border-gray-300 text-gray-700 py-2.5 rounded-lg text-sm hover:bg-gray-50 transition">إلغاء</button>
        </div>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.x/dist/signature_pad.umd.min.js"></script>
<script>
function settlementPage() {
    return { withdrawalModal: false, init() {} }
}

const sigPads = {};

function initSigCanvas(id) {
    const canvas = document.getElementById(id);
    if (!canvas) return;
    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;
    sigPads[id] = new SignaturePad(canvas);
}

function clearCanvas(canvasId, inputId) {
    if (sigPads[canvasId]) sigPads[canvasId].clear();
    const inp = document.getElementById(inputId);
    if (inp) inp.value = '';
}

function captureSignatures() {
    const emp = sigPads['employeeSigCanvas'];
    const adm = sigPads['adminSigCanvas'];
    const empInp = document.getElementById('employee_signature');
    const admInp = document.getElementById('admin_signature');
    if (emp && !emp.isEmpty() && empInp) empInp.value = emp.toDataURL();
    if (adm && !adm.isEmpty() && admInp) admInp.value = adm.toDataURL();
    return true;
}

document.addEventListener('DOMContentLoaded', () => {
    initSigCanvas('employeeSigCanvas');
    initSigCanvas('adminSigCanvas');
});
</script>
@endpush
