@extends('layouts.app')
@section('title', 'دفعة موحدة لعدة حجوزات')
@section('page-title', 'دفعة موحدة لعدة حجوزات')
@section('back-url', route('reservations.expiring'))

@section('content')
@php
    // البيانات الأولية للحجوزات ذات الرصيد — تُمرَّر لـ Alpine كنقطة انطلاق،
    // ويُحدّثها البحث عبر AJAX لاحقاً.
    $initialReservations = $reservations->map(fn ($r) => [
        'id'          => $r->id,
        'guest_name'  => $r->guest?->full_name ?? '—',
        'room_number' => $r->display_room_number,
        'check_in'    => $r->check_in_date?->format('d/m/Y'),
        'check_out'   => $r->check_out_date?->format('d/m/Y'),
        'nights'      => $r->nights,
        'total'       => round((float) $r->total_amount, 2),
        'paid'        => round((float) $r->paid_amount, 2),
        'balance'     => round($r->balance, 2),
    ])->values();
@endphp

<div class="max-w-5xl mx-auto" x-data="groupPayment()">

    {{-- شرح الميزة --}}
    <div class="mb-5 rounded-2xl bg-gradient-to-l from-blue-50 to-indigo-50 border border-blue-100 p-4 flex items-start gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-500 flex items-center justify-center shrink-0 shadow-sm">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m3-6h8a2 2 0 012 2v6a2 2 0 01-2 2h-8a2 2 0 01-2-2V9a2 2 0 012-2z"/></svg>
        </div>
        <div class="text-sm text-blue-900">
            <p class="font-bold mb-0.5">دفعة واحدة تُوزَّع على عدة حجوزات</p>
            <p class="text-blue-700 leading-relaxed">استخدم هذه الشاشة عندما يدفع شخص واحد المبلغ الإجمالي لعدة حجوزات (أب يدفع لأبنائه، أو نزيل حجز عدة غرف باسمه). اختر الحجوزات، ثم وزّع المبلغ تلقائياً أو يدوياً.</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-5 gap-5">

        {{-- ===== العمود الأيمن: اختيار الحجوزات ===== --}}
        <div class="lg:col-span-3 space-y-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-black">1</span>
                        اختيار الحجوزات
                    </h3>
                    <span class="text-xs text-gray-400" x-text="reservations.length + ' حجز متاح'"></span>
                </div>

                {{-- شريط البحث --}}
                <div class="p-4 border-b border-gray-50">
                    <div class="relative">
                        <input type="text" x-model="searchTerm" @input.debounce.400ms="runSearch()"
                               placeholder="ابحث بالاسم أو رقم الغرفة أو رقم الحجز..."
                               class="w-full border border-gray-300 rounded-xl pr-10 pl-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        <svg class="w-4 h-4 text-gray-400 absolute right-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>

                {{-- قائمة الحجوزات --}}
                <div class="divide-y divide-gray-50 max-h-[26rem] overflow-y-auto">
                    <template x-for="r in reservations" :key="r.id">
                        <label class="flex items-center gap-3 px-4 py-3 hover:bg-blue-50/40 cursor-pointer transition"
                               :class="isSelected(r.id) ? 'bg-blue-50/60' : ''">
                            <input type="checkbox" :checked="isSelected(r.id)" @change="toggle(r)"
                                   class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 shrink-0">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-800 text-sm truncate" x-text="r.guest_name"></span>
                                    <span class="text-[11px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 shrink-0" x-text="'غرفة ' + r.room_number"></span>
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5" dir="ltr">
                                    <span x-text="'#' + r.id"></span> ·
                                    <span x-text="r.check_in + ' → ' + r.check_out"></span> ·
                                    <span x-text="r.nights + ' ليلة'"></span>
                                </div>
                            </div>
                            <div class="text-left shrink-0">
                                <div class="text-[11px] text-gray-400">الرصيد المتبقّي</div>
                                <div class="font-bold text-red-600 text-sm" x-text="fmt(r.balance) + ' ر.ي'"></div>
                            </div>
                        </label>
                    </template>

                    <div x-show="reservations.length === 0" class="px-4 py-10 text-center text-sm text-gray-400">
                        لا توجد حجوزات ذات رصيد متبقٍّ مطابقة للبحث
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== العمود الأيسر: التوزيع والدفع ===== --}}
        <div class="lg:col-span-2 space-y-4">
            <form method="POST" action="{{ route('payments.group.store') }}" enctype="multipart/form-data"
                  @submit="return validateBeforeSubmit($event)">
                @csrf

                {{-- الحجوزات المختارة + التوزيع --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-4">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-black">2</span>
                            التوزيع
                        </h3>
                        <span class="text-xs text-gray-400" x-text="selected.length + ' مختار'"></span>
                    </div>

                    <div x-show="selected.length === 0" class="px-4 py-8 text-center text-sm text-gray-400">
                        اختر حجوزات من القائمة لتظهر هنا
                    </div>

                    <div x-show="selected.length > 0" class="p-4 space-y-3">
                        {{-- المبلغ الإجمالي + توزيع تلقائي --}}
                        <div class="rounded-xl bg-gray-50 border border-gray-100 p-3">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">المبلغ الإجمالي المدفوع</label>
                            <div class="flex gap-2">
                                <input type="number" x-model.number="totalInput" min="0" step="0.01"
                                       placeholder="0"
                                       class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                                <button type="button" @click="distributeEqually()"
                                        class="px-3 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold whitespace-nowrap transition">
                                    توزيع متساوٍ
                                </button>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1.5">اكتب المبلغ ثم اضغط «توزيع متساوٍ»، أو وزّع يدوياً بالأسفل.</p>
                        </div>

                        {{-- بنود التوزيع لكل حجز --}}
                        <div class="space-y-2">
                            <template x-for="item in selected" :key="item.id">
                                <div class="rounded-xl border border-gray-100 p-3">
                                    <div class="flex items-center justify-between gap-2 mb-1.5">
                                        <span class="font-semibold text-gray-700 text-xs truncate" x-text="item.guest_name + ' · غرفة ' + item.room_number"></span>
                                        <button type="button" @click="remove(item.id)" class="text-gray-300 hover:text-red-500 shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input type="number" :name="'amounts[' + item.id + ']'" x-model.number="item.amount"
                                               min="0" step="0.01" :max="item.balance"
                                               @input="clampAmount(item)"
                                               class="flex-1 border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                                               :class="item.amount > item.balance + 0.01 ? 'border-red-400 bg-red-50' : 'border-gray-300'">
                                        <span class="text-[11px] text-gray-400 whitespace-nowrap">من <span x-text="fmt(item.balance)"></span></span>
                                    </div>
                                    <input type="hidden" name="reservation_ids[]" :value="item.id">
                                    <p x-show="item.amount > item.balance + 0.01" class="text-[11px] text-red-500 mt-1">المبلغ يتجاوز الرصيد المتبقّي</p>
                                </div>
                            </template>
                        </div>

                        {{-- ملخص المجموع --}}
                        <div class="rounded-xl p-3 flex items-center justify-between"
                             :class="sumMatches ? 'bg-emerald-50 border border-emerald-200' : 'bg-amber-50 border border-amber-200'">
                            <span class="text-xs font-semibold" :class="sumMatches ? 'text-emerald-700' : 'text-amber-700'">مجموع الموزَّع</span>
                            <span class="font-black" :class="sumMatches ? 'text-emerald-700' : 'text-amber-700'">
                                <span x-text="fmt(distributedSum)"></span> ر.ي
                            </span>
                        </div>
                    </div>
                </div>

                {{-- بيانات الدفعة --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-black">3</span>
                            بيانات الدفعة
                        </h3>
                    </div>
                    <div class="p-4 space-y-3">
                        {{-- طريقة الدفع --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">طريقة الدفع <span class="text-red-500">*</span></label>
                            <select name="method" x-model="method" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="cash">نقداً</option>
                                <option value="pos">شبكة (POS)</option>
                                <option value="bank_transfer">تحويل بنكي</option>
                            </select>
                        </div>

                        {{-- حقول التحويل البنكي --}}
                        <div x-show="method === 'bank_transfer'" x-cloak class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">رقم مرجع التحويل</label>
                                <input type="text" name="bank_transfer_ref" maxlength="100"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">صورة السند</label>
                                <input type="file" name="bank_receipt" accept=".jpg,.jpeg,.png,.pdf"
                                       class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 file:text-xs file:font-semibold">
                            </div>
                        </div>

                        {{-- اسم من يدفع --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">اسم من يدفع <span class="text-gray-400 font-normal">(اختياري)</span></label>
                            <input type="text" name="paid_by_name" maxlength="150"
                                   placeholder="مثال: الأب / الشركة / الوكيل"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>

                        {{-- ملاحظات --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">ملاحظات <span class="text-gray-400 font-normal">(اختياري)</span></label>
                            <textarea name="notes" rows="2" maxlength="500"
                                      placeholder="مثال: دفعة موحدة من الأب لأبنائه"
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
                        </div>

                        {{-- زر الحفظ --}}
                        <button type="submit"
                                :disabled="!canSubmit"
                                class="w-full px-4 py-3 rounded-xl text-white text-sm font-bold transition"
                                :class="canSubmit ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-gray-300 cursor-not-allowed'">
                            <span x-show="canSubmit">تسجيل الدفعة الموحدة (<span x-text="fmt(distributedSum)"></span> ر.ي)</span>
                            <span x-show="!canSubmit">اختر حجوزات وحدّد المبالغ</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function groupPayment() {
    return {
        reservations: @json($initialReservations),
        selected: [],
        searchTerm: '',
        totalInput: null,
        method: 'cash',

        isSelected(id) {
            return this.selected.some(s => s.id === id);
        },
        toggle(r) {
            if (this.isSelected(r.id)) {
                this.remove(r.id);
            } else {
                this.selected.push({ id: r.id, guest_name: r.guest_name, room_number: r.room_number, balance: r.balance, amount: r.balance });
            }
        },
        remove(id) {
            this.selected = this.selected.filter(s => s.id !== id);
        },
        clampAmount(item) {
            if (item.amount < 0) item.amount = 0;
        },
        distributeEqually() {
            const total = parseFloat(this.totalInput) || 0;
            if (total <= 0 || this.selected.length === 0) return;
            // توزيع متساوٍ مع احترام رصيد كل حجز: نوزّع بالتساوي ثم نقصّ ما يتجاوز
            // الرصيد ونعيد توزيع الفائض على البقية (حتى لا يضيع أو يتجاوز رصيداً).
            let remaining = total;
            let pending = this.selected.map(s => s);
            pending.forEach(s => s.amount = 0);
            while (remaining > 0.01 && pending.some(s => s.amount < s.balance - 0.01)) {
                const eligible = pending.filter(s => s.amount < s.balance - 0.01);
                const share = remaining / eligible.length;
                remaining = 0;
                eligible.forEach(s => {
                    const room = s.balance - s.amount;
                    const add = Math.min(share, room);
                    s.amount = Math.round((s.amount + add) * 100) / 100;
                    remaining += (share - add);
                });
            }
        },
        get distributedSum() {
            return Math.round(this.selected.reduce((a, s) => a + (parseFloat(s.amount) || 0), 0) * 100) / 100;
        },
        get sumMatches() {
            const total = parseFloat(this.totalInput) || 0;
            if (total <= 0) return true;
            return Math.abs(this.distributedSum - total) < 0.01;
        },
        get hasOverflow() {
            return this.selected.some(s => (parseFloat(s.amount) || 0) > s.balance + 0.01);
        },
        get canSubmit() {
            return this.selected.length > 0 && this.distributedSum > 0 && !this.hasOverflow;
        },
        validateBeforeSubmit(e) {
            if (!this.canSubmit) {
                e.preventDefault();
                return false;
            }
            if (this.hasOverflow) {
                e.preventDefault();
                alert('أحد المبالغ يتجاوز رصيد حجزه. صحّح التوزيع أولاً.');
                return false;
            }
            return true;
        },
        fmt(n) {
            return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Math.round(n || 0));
        },
        async runSearch() {
            try {
                const url = new URL('{{ route('payments.group.search') }}', window.location.origin);
                url.searchParams.set('q', this.searchTerm);
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                this.reservations = data.reservations || [];
            } catch (err) {
                console.error('search failed', err);
            }
        },
    };
}
</script>
@endpush
@endsection
