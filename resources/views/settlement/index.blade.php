@extends('layouts.app')
@section('title', 'التسوية النقدية')
@section('page-title', 'التسوية النقدية اليومية')

@section('content')
<div x-data="settlementPage()" x-init="init()">

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

<!-- Totals -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-5">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="text-xs text-gray-500 mb-1">إجمالي المستلم</div>
        <div class="text-3xl font-bold text-green-700">{{ number_format($settlement->total_received, 2) }}</div>
        <div class="text-xs text-gray-400 mt-1">ريال يمني</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="text-xs text-gray-500 mb-1">إجمالي المصروفات</div>
        <div class="text-3xl font-bold text-red-700">{{ number_format($settlement->total_withdrawals, 2) }}</div>
        <div class="text-xs text-gray-400 mt-1">ريال يمني</div>
    </div>
    <div class="bg-primary-50 border border-primary-200 rounded-xl p-5" style="background:#e8f0f7; border-color:#9fbedd;">
        <div class="text-xs mb-1" style="color:#0F4C75;">الرصيد الصافي</div>
        <div class="text-3xl font-bold" style="color:#0F4C75;">{{ number_format($settlement->net_balance, 2) }}</div>
        <div class="text-xs mt-1" style="color:#5b90c5;">ريال يمني</div>
    </div>
</div>

<!-- Withdrawals -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-5">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-700">المصروفات والسحوبات</h3>
        @if($settlement->status === 'open')
        @can('settlement.manage')
        <button @click="withdrawalModal=true"
                class="flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            إضافة سحب
        </button>
        @endcan
        @endif
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المبلغ</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المستلم</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المسلِّم</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">ملاحظات</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($settlement->withdrawals as $w)
                <tr>
                    <td class="px-4 py-3 text-gray-600">{{ \Carbon\Carbon::parse($w->withdrawal_date)->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3 font-bold text-red-600">{{ number_format($w->amount, 2) }} {{ $w->currency }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $w->withdrawn_by_name }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $w->handed_by_name }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $w->notes ?: '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400 text-sm">لا توجد سحوبات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
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

<!-- Withdrawal Modal -->
<div x-show="withdrawalModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="withdrawalModal=false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-800">إضافة سحب</h3>
            <button @click="withdrawalModal=false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('settlement.withdrawal') }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">المبلغ *</label>
                <input type="number" name="amount" step="0.01" min="0.01" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 outline-none" style="--tw-ring-color:#0F4C75;">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">العملة</label>
                <select name="currency" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none">
                    <option value="YER">ريال يمني</option>
                    <option value="SAR">ريال سعودي</option>
                    <option value="USD">دولار أمريكي</option>
                </select>
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
            <button type="submit" class="w-full text-white py-2.5 rounded-lg font-semibold transition text-sm" style="background:#0F4C75;">إضافة السحب</button>
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
