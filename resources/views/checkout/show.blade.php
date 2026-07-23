@extends('layouts.app')
@section('title', 'تسجيل الخروج')
@section('page-title', 'تسجيل الخروج')
@section('back-url', route('reservations.index'))

@section('content')
<div x-data="checkoutForm()" class="max-w-3xl mx-auto space-y-5"
     x-init="balance = {{ $reservation->balance }}">

<!-- Guest Summary -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h3 class="font-semibold text-gray-700 mb-4">ملخص الحجز</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gray-50 rounded-lg p-3 text-center">
            <div class="text-xs text-gray-500 mb-1">النزيل</div>
            <div class="font-semibold text-gray-800 text-sm">{{ $reservation->guest?->full_name ?? '—' }}</div>
        </div>
        <div class="bg-gray-50 rounded-lg p-3 text-center">
            <div class="text-xs text-gray-500 mb-1">الغرفة</div>
            <div class="font-bold text-primary-800 text-lg">{{ $reservation->display_room_number }}</div>
        </div>
        <div class="bg-gray-50 rounded-lg p-3 text-center">
            <div class="text-xs text-gray-500 mb-1">تاريخ الدخول</div>
            <div class="font-medium text-gray-700 text-sm">{{ $reservation->check_in_date?->format('d/m/Y') ?? '—' }}</div>
        </div>
        <div class="bg-gray-50 rounded-lg p-3 text-center">
            <div class="text-xs text-gray-500 mb-1">تاريخ الخروج</div>
            <div class="font-medium text-gray-700 text-sm">{{ $reservation->check_out_date?->format('d/m/Y') ?? '—' }}</div>
        </div>
    </div>

    <!-- Balance Summary -->
    <div class="mt-4 grid grid-cols-3 gap-3">
        <div class="bg-blue-50 rounded-lg p-3 text-center border border-blue-100">
            <div class="text-xs text-blue-500 mb-1">الإجمالي</div>
            <div class="font-bold text-blue-800">{{ number_format($reservation->total_amount, 2) }}</div>
            <div class="text-xs text-blue-400">{{ $reservation->currency_symbol }}</div>
        </div>
        <div class="bg-green-50 rounded-lg p-3 text-center border border-green-100">
            <div class="text-xs text-green-500 mb-1">المدفوع</div>
            <div class="font-bold text-green-800">{{ number_format($reservation->paid_amount, 2) }}</div>
            <div class="text-xs text-green-400">{{ $reservation->currency_symbol }}</div>
        </div>
        <div class="rounded-lg p-3 text-center border {{ $reservation->balance > 0 ? 'bg-red-50 border-red-100' : 'bg-gray-50 border-gray-100' }}">
            <div class="text-xs {{ $reservation->balance > 0 ? 'text-red-500' : 'text-gray-500' }} mb-1">المتبقي</div>
            <div class="font-bold {{ $reservation->balance > 0 ? 'text-red-800' : 'text-gray-800' }}">{{ number_format($reservation->balance, 2) }}</div>
            <div class="text-xs {{ $reservation->balance > 0 ? 'text-red-400' : 'text-gray-400' }}">{{ $reservation->currency_symbol }}</div>
        </div>
    </div>

    {{-- تفصيل الحساب: إجمالي الغرفة قبل الخصم، الخصم، الرسوم/الأضرار، ثم الإجمالي (إقامة الفندق) --}}
    @php
        $grossTotal    = $reservation->gross_total;
        $hotelCharges  = $reservation->hotel_charges_total;
        $purchasesDebt = $reservation->purchases_debt;
    @endphp
    <div class="mt-4 bg-gray-50 rounded-xl border border-gray-200 p-4 space-y-2 text-sm">
        <div class="flex items-center justify-between text-gray-600">
            <span>إجمالي الغرفة (قبل الخصم)</span>
            <span class="font-semibold text-gray-800">{{ number_format($grossTotal, 2) }} {{ $reservation->currency_symbol }}</span>
        </div>
        @if($reservation->discount_amount > 0)
        <div class="flex items-center justify-between text-emerald-600">
            <span>خصم {{ $reservation->discount_reason ? '(' . $reservation->discount_reason . ')' : '' }}</span>
            <span class="font-semibold">- {{ number_format($reservation->discount_amount, 2) }} {{ $reservation->currency_symbol }}</span>
        </div>
        @endif
        @if($hotelCharges > 0)
        <div class="flex items-center justify-between text-red-600">
            <span>رسوم/أضرار مُحتسبة</span>
            <span class="font-semibold">+ {{ number_format($hotelCharges, 2) }} {{ $reservation->currency_symbol }}</span>
        </div>
        @endif
        <div class="border-t border-gray-200 pt-2 flex items-center justify-between">
            <span class="font-bold text-gray-800">إجمالي الإقامة المستحق</span>
            <span class="font-black text-gray-900">{{ number_format($reservation->total_amount, 2) }} {{ $reservation->currency_symbol }}</span>
        </div>
    </div>

    {{-- دَين المشتريات (بقالة) — منفصل عن صندوق الفندق، يُحصَّل ويُسلَّم للبقالة --}}
    @if($purchasesDebt > 0)
    <div class="mt-4 bg-amber-50 rounded-xl border border-amber-200 p-4 text-sm">
        <div class="flex items-center justify-between">
            <span class="font-bold text-amber-800 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                دَين مشتريات (بقالة) غير محصَّل
            </span>
            <span class="font-black text-amber-900">{{ number_format($purchasesDebt, 2) }} {{ $reservation->currency_symbol }}</span>
        </div>
        <p class="text-xs text-amber-700 mt-1.5">يُحصَّل من النزيل ويُسلَّم للبقالة — لا يدخل صندوق الفندق ولا تقاريره. يجب تحصيله قبل إتمام الخروج.</p>
    </div>
    @endif
</div>

<form method="POST" action="{{ route('checkout.process', $reservation) }}" enctype="multipart/form-data" class="space-y-5">
    @csrf
    @method('POST')

    <!-- Room Inspection -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 mb-4">فحص الغرفة</h3>

        <div class="flex items-center gap-3 mb-4">
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="has_damage" value="1" x-model="hasDamage" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-red-500 after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all rtl:peer-checked:after:-translate-x-full"></div>
            </label>
            <span class="text-sm font-medium" :class="hasDamage ? 'text-red-700' : 'text-gray-700'">
                <span x-text="hasDamage ? 'يوجد أضرار في الغرفة' : 'لا توجد أضرار'"></span>
            </span>
        </div>

        <div x-show="hasDamage" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">وصف الأضرار *</label>
                <textarea name="damage_description" rows="3" :required="hasDamage"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 outline-none resize-none"
                          placeholder="صف الأضرار بالتفصيل..."></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">صور الأضرار</label>
                <input type="file" name="inspection_images[]" accept="image/*" multiple
                       class="w-full text-sm text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">مبلغ التعويض</label>
                <input type="number" name="compensation_amount" x-model="compensationAmount" step="0.01" min="0"
                       class="w-full md:w-64 border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>

            <div x-show="compensationAmount > 0" class="bg-orange-50 border border-orange-200 rounded-lg p-3 text-sm text-orange-800">
                سيُضاف مبلغ التعويض إلى الإجمالي المطلوب في قسم الدفع أدناه.
            </div>
        </div>
    </div>

    <!-- Payment Section (balance + damage combined) -->
    <div x-show="totalRequired > 0" class="bg-white rounded-xl shadow-sm border border-red-200 p-5">
        <div class="flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <h3 class="font-semibold text-red-700">تسوية المبالغ المستحقة — مطلوب قبل الخروج</h3>
        </div>

        {{-- Breakdown when damage exists --}}
        <div x-show="hasDamage && compensationAmount > 0" class="mb-4 space-y-2 bg-gray-50 rounded-xl p-4 border border-gray-200 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-600">رصيد الحجز</span>
                <span class="font-medium text-gray-800">{{ number_format($reservation->balance, 2) }} {{ $reservation->currency_symbol }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">تعويض الأضرار</span>
                <span class="font-medium text-red-700" x-text="parseFloat(compensationAmount || 0).toLocaleString('ar-SA', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' {{ $reservation->currency_symbol }}'"></span>
            </div>
            <div class="flex justify-between border-t border-gray-300 pt-2 font-semibold">
                <span class="text-gray-800">الإجمالي المطلوب</span>
                <span class="text-red-700" x-text="totalRequired.toLocaleString('ar-SA', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' {{ $reservation->currency_symbol }}'"></span>
            </div>
        </div>

        {{-- Simple total when no damage --}}
        <div x-show="!(hasDamage && compensationAmount > 0)" class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4 flex items-center justify-between">
            <span class="text-sm text-red-800 font-medium">المبلغ المتبقي</span>
            <span class="text-xl font-bold text-red-700">{{ number_format($reservation->balance, 2) }} {{ $reservation->currency_symbol }}</span>
        </div>

        <input type="hidden" name="currency" value="YER">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">المبلغ المدفوع <span class="text-red-500">*</span></label>
                <input type="number" name="remaining_payment" x-model.number="remainingPayment"
                       step="0.01"
                       :min="leftUnpaid ? 0 : totalRequired" :max="totalRequired"
                       :placeholder="leftUnpaid ? '0.00' : totalRequired.toFixed(2)"
                       class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                <p x-show="remainingPayment > 0 && remainingPayment != totalRequired"
                   class="text-xs text-red-600 mt-1 font-medium">
                    يجب أن يكون المبلغ مساوياً للإجمالي المطلوب تماماً (<span x-text="totalRequired.toLocaleString('ar-SA', {minimumFractionDigits:2, maximumFractionDigits:2})"></span> {{ $reservation->currency_symbol }})
                </p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">طريقة الدفع</label>
                <select name="remaining_method" x-model="remainingMethod"
                        class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                    <option value="cash">نقدي</option>
                    <option value="pos">POS</option>
                    <option value="bank_transfer">تحويل بنكي</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">ملاحظة (اختياري)</label>
                <input type="text" name="payment_notes"
                       placeholder="مثال: دفع 100 ر.س بسعر صرف 400 = 40,000 ر.ي"
                       class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <div x-show="remainingMethod === 'bank_transfer'" class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">سند التحويل <span class="text-red-500">*</span></label>
                <input type="file" name="remaining_bank_receipt" accept="image/*,.pdf"
                       class="w-full text-sm text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>
        </div>
    </div>

    <!-- Submit -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        {{-- تحصيل دَين المشتريات (بقالة) — منفصل عن صندوق الفندق، إلزامي قبل الخروج --}}
        @if($reservation->purchases_debt > 0)
        <input type="hidden" name="collect_purchases" :value="collectPurchases ? 1 : 0">
        <div class="mb-4">
            <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition"
                   :class="collectPurchases ? 'bg-amber-50 border-amber-300' : 'bg-gray-50 border-gray-200 hover:border-gray-300'">
                <input type="checkbox" x-model="collectPurchases" class="mt-0.5 w-4 h-4 accent-amber-600">
                <span class="text-sm">
                    <span class="font-semibold text-gray-800">تم تحصيل دَين المشتريات (بقالة) وتسليمه للبقالة — {{ number_format($reservation->purchases_debt, 0) }} {{ $reservation->currency_symbol }}</span>
                    <span class="block text-xs text-gray-500 mt-0.5">لا يدخل صندوق الفندق ولا تقاريره. يجب تأكيد تحصيله قبل الخروج (أو اختَر «غادر دون سداد» لتركه كدَين).</span>
                </span>
            </label>
        </div>
        <div x-show="purchasesDebt > 0 && !collectPurchases && !leftUnpaid"
             class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4 flex items-center gap-3 text-sm text-red-800">
            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            لا يمكن تسجيل الخروج — يوجد دَين مشتريات (بقالة) غير محصَّل يجب تحصيله أولاً، أو اختَر «غادر دون سداد».
        </div>
        @endif

        {{-- خيار: النزيل غادر دون سداد (هروب) — يُسجَّل الخروج مع بقاء الدين --}}
        <div x-show="totalRequired > 0 || purchasesDebt > 0" class="mb-4">
            <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition"
                   :class="leftUnpaid ? 'bg-orange-50 border-orange-300' : 'bg-gray-50 border-gray-200 hover:border-gray-300'">
                <input type="checkbox" name="left_unpaid" value="1" x-model="leftUnpaid" class="mt-0.5 w-4 h-4 accent-orange-600">
                <span class="text-sm">
                    <span class="font-semibold text-gray-800">النزيل غادر دون سداد المبلغ المتبقي</span>
                    <span class="block text-xs text-gray-500 mt-0.5">اختر هذا الخيار إذا غادر النزيل دون دفع (هروب). سيُسجَّل الخروج ويبقى المبلغ المتبقي كدَين عليه يظهر في تقرير الديون.</span>
                </span>
            </label>
        </div>

        {{-- Block checkout if total required > 0 and no payment entered (unless marked left-unpaid) --}}
        <div x-show="totalRequired > 0 && !leftUnpaid && remainingPayment <= 0"
             class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4 flex items-center gap-3 text-sm text-red-800">
            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            لا يمكن تسجيل الخروج — يوجد مبالغ مستحقة بلغت <strong class="mx-1" x-text="totalRequired.toLocaleString('ar-SA', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' {{ $reservation->currency_symbol }}'"></strong> يجب تسويتها أولاً، أو اختَر «غادر دون سداد» أعلاه.
        </div>
        <div x-show="totalRequired > 0 && !leftUnpaid && remainingPayment > 0 && remainingPayment != totalRequired"
             class="bg-orange-50 border border-orange-200 rounded-xl p-4 mb-4 flex items-center gap-3 text-sm text-orange-800">
            <svg class="w-5 h-5 text-orange-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            المبلغ المدخل لا يساوي الإجمالي المطلوب (<span x-text="totalRequired.toLocaleString('ar-SA', {minimumFractionDigits:2, maximumFractionDigits:2})"></span> {{ $reservation->currency_symbol }})
        </div>
        <div x-show="totalRequired > 0 && leftUnpaid"
             class="bg-orange-50 border border-orange-200 rounded-xl p-4 mb-4 flex items-center gap-3 text-sm text-orange-800">
            <svg class="w-5 h-5 text-orange-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            سيُسجَّل الخروج مع بقاء دَين قدره <strong class="mx-1" x-text="(totalRequired - (parseFloat(remainingPayment)||0)).toLocaleString('ar-SA', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' {{ $reservation->currency_symbol }}'"></strong> على النزيل.
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-3 mb-4 text-sm text-yellow-800">
            <strong>تحذير:</strong> بعد إتمام تسجيل الخروج لا يمكن التراجع عنه.
        </div>
        <div class="flex gap-3">
            <button type="submit"
                    :disabled="blocked"
                    class="flex items-center gap-2 px-6 py-3 bg-red-600 text-white rounded-xl font-semibold hover:bg-red-700 transition disabled:opacity-40 disabled:cursor-not-allowed">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                إتمام تسجيل الخروج
            </button>
            <a href="{{ route('reservations.show', $reservation) }}" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition text-sm">إلغاء</a>
        </div>
    </div>
</form>
</div>
@endsection

@push('scripts')
<script>
function checkoutForm() {
    return {
        hasDamage: false,
        compensationAmount: 0,
        remainingMethod: 'cash',
        remainingPayment: 0,
        leftUnpaid: false,
        balance: {{ $reservation->balance }},
        purchasesDebt: {{ $reservation->purchases_debt }},
        collectPurchases: false,
        get totalRequired() {
            const comp = this.hasDamage ? (parseFloat(this.compensationAmount) || 0) : 0;
            return this.balance + comp;
        },
        // يُمنع الخروج ما لم تُسوَّ الإقامة (أو تُترك كدَين) ويُحصَّل دَين المشتريات
        // (أو يُترك كدَين عند خيار «غادر دون سداد»).
        get blocked() {
            const stayBlocked = this.totalRequired > 0 && !this.leftUnpaid
                && (this.remainingPayment <= 0 || this.remainingPayment != this.totalRequired);
            const purchasesBlocked = this.purchasesDebt > 0 && !this.collectPurchases && !this.leftUnpaid;
            return stayBlocked || purchasesBlocked;
        },
    }
}
</script>
@endpush
