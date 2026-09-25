{{--
    نوافذ العمل السريع في جدول الحجوزات: تجديد الإقامة، رسم على الغرفة،
    والملاحظات الفورية. كلها تعمل عبر JSON دون مغادرة الصفحة، فيتابع الموظف
    نزيلاً بعد نزيل بلا انتظار تحميل صفحات.
--}}

{{-- ═══ تنبيه عائم (Toast) ═══ --}}
<div id="qaToast" class="hidden fixed bottom-6 left-6 z-[70] max-w-sm rounded-xl shadow-2xl px-5 py-4 text-sm font-semibold text-white"></div>

{{-- ═══ تجديد الإقامة ═══ --}}
@can('reservation.renew')
<div id="renewQuickModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[60] p-4"
     onclick="if(event.target===this) closeQuick('renewQuickModal')">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" onclick="event.stopPropagation()">
        <div class="px-6 py-4 rounded-t-2xl flex items-center justify-between" style="background:linear-gradient(135deg,#047857,#10b981);">
            <h3 class="font-bold text-white">تجديد الإقامة</h3>
            <button type="button" onclick="closeQuick('renewQuickModal')" class="text-white/80 hover:text-white">✕</button>
        </div>
        <form id="renewQuickForm" class="p-6 space-y-4">
            @csrf
            <p class="text-sm text-gray-600" id="renewQuickWho"></p>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">عدد الليالي</label>
                    <div class="flex items-center gap-1">
                        <button type="button" onclick="bumpNights(-1)" class="w-9 h-10 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50">−</button>
                        <input type="number" id="renewQuickNights" min="1" value="1" oninput="syncRenewQuick()"
                               class="w-full text-center border border-gray-300 rounded-lg px-2 py-2 text-sm outline-none">
                        <button type="button" onclick="bumpNights(1)" class="w-9 h-10 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50">+</button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">تاريخ الخروج الجديد</label>
                    <input type="date" name="new_check_out_date" id="renewQuickDate" required onchange="syncRenewQuickFromDate()"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">سعر الليلة</label>
                    <input type="number" name="renewal_price" id="renewQuickPrice" min="0" step="0.01" oninput="syncRenewQuick()"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">دفعة مقدمة <span class="font-normal text-gray-400">(اختياري)</span></label>
                    <input type="number" name="advance_payment" id="renewQuickAdvance" min="0" step="0.01" placeholder="0" oninput="syncRenewQuick()"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                </div>
            </div>

            <div id="renewQuickPayBox" class="hidden">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">طريقة الدفع</label>
                <select name="payment_method" id="renewQuickMethod" onchange="syncRenewQuick()"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="cash">نقدي</option>
                    <option value="pos">شبكة POS</option>
                    <option value="bank_transfer">تحويل بنكي</option>
                </select>
                {{-- التحويل البنكي يحتاج إثباتاً؛ وإدخاله هنا يطول، فنوجّه لصفحة التفاصيل --}}
                <p id="renewQuickBankHint" class="hidden text-xs text-blue-700 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2 mt-2">
                    التحويل البنكي يحتاج إرفاق السند — سجّل الدفعة من صفحة تفاصيل الحجز بعد التجديد.
                </p>
            </div>

            <div class="rounded-xl bg-emerald-50 border border-emerald-100 px-4 py-3 flex items-center justify-between">
                <span class="text-xs text-emerald-700 font-semibold">قيمة التجديد</span>
                <span class="text-xl font-black text-emerald-800" id="renewQuickTotal">0</span>
            </div>

            <div class="flex gap-3">
                <button type="submit" id="renewQuickSubmit"
                        class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-sm transition">
                    تأكيد التجديد
                </button>
                <button type="button" onclick="closeQuick('renewQuickModal')"
                        class="px-5 py-3 border border-gray-300 text-gray-600 rounded-xl text-sm hover:bg-gray-50">إلغاء</button>
            </div>
        </form>
    </div>
</div>
@endcan

{{-- ═══ رسم على الغرفة ═══ --}}
@can('payments.create')
<div id="chargeQuickModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[60] p-4"
     onclick="if(event.target===this) closeQuick('chargeQuickModal')">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" onclick="event.stopPropagation()">
        <div class="px-6 py-4 rounded-t-2xl flex items-center justify-between" style="background:linear-gradient(135deg,#b45309,#f59e0b);">
            <h3 class="font-bold text-white">إضافة رسم على الغرفة</h3>
            <button type="button" onclick="closeQuick('chargeQuickModal')" class="text-white/80 hover:text-white">✕</button>
        </div>
        <form id="chargeQuickForm" class="p-6 space-y-4">
            @csrf
            <p class="text-sm text-gray-600" id="chargeQuickWho"></p>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">نوع الرسم *</label>
                <select name="charge_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none">
                    @foreach(\App\Models\ExtraCharge::HOTEL_TYPES as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">المبلغ (ر.ي) *</label>
                <input type="number" name="amount" min="0.01" step="0.01" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">ملاحظة توضّح السبب</label>
                <input type="text" name="description" maxlength="255" placeholder="مثال: تأخّر المغادرة إلى الساعة 6 مساءً"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none">
            </div>

            <p class="text-xs text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                يُضاف المبلغ إلى إجمالي الحجز، ويظهر بنداً مستقلاً في الفاتورة وفي إيرادات الفندق.
            </p>

            <div class="flex gap-3">
                <button type="submit" id="chargeQuickSubmit"
                        class="flex-1 py-3 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-bold text-sm transition">
                    إضافة الرسم
                </button>
                <button type="button" onclick="closeQuick('chargeQuickModal')"
                        class="px-5 py-3 border border-gray-300 text-gray-600 rounded-xl text-sm hover:bg-gray-50">إلغاء</button>
            </div>
        </form>
    </div>
</div>
@endcan

{{-- ═══ الملاحظات الفورية ═══ --}}
<div id="notesPopover" class="hidden fixed z-[65] w-80 bg-white rounded-xl shadow-2xl border border-gray-200">
    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
        <h4 class="font-bold text-gray-800 text-sm">ملاحظات الحجز <span id="notesResId" class="text-gray-400 font-normal"></span></h4>
        <div class="flex items-center gap-1">
            <button type="button" onclick="toggleNotesCollapse()" id="notesCollapseBtn"
                    class="w-7 h-7 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition" title="تصغير">–</button>
            <button type="button" onclick="closeNotes()" class="w-7 h-7 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">✕</button>
        </div>
    </div>

    <div id="notesBody">
        <div id="notesList" class="max-h-56 overflow-y-auto divide-y divide-gray-50"></div>

        <form id="noteForm" class="p-3 border-t border-gray-100 space-y-2">
            @csrf
            <select name="type" class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs outline-none">
                @foreach(\App\Models\ReservationNote::TYPES as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <textarea name="body" rows="2" required maxlength="1000" placeholder="اكتب الملاحظة…"
                      class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs outline-none resize-none"></textarea>
            <button type="submit" class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition">
                إضافة ملاحظة
            </button>
        </form>
    </div>
</div>
