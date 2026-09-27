@extends('layouts.app')
@section('title', 'النزلاء المسجلون - ترتيب حسب الخروج')
@section('page-title', 'النزلاء المسجلون')

@push('styles')
<style>
    /* صفّ النزيل المُغادِر: رمادي خفيف في الوضع النهاري، وشفاف يندمج مع
       البطاقة في الوضع الليلي (بدل الأبيض). الـ hover يبقى كحركة لطيفة. */
    .row-departed { background-color: rgba(249,250,251,.7); }
    html.dark .row-departed { background-color: transparent !important; }
    /* صفّ "اليوم" (كان غير مُعالَج في الوضع الليلي فيظهر برتقالياً فاتحاً) */
    html.dark .bg-orange-50 { background-color: rgba(249,115,22,.10) !important; }
</style>
@endpush

@section('content')
<div dir="rtl">

{{-- لوحة الملاحظات العامة — أعلى الصفحة ليقرأها كل موظف قبل أي شيء --}}
@include('reservations._hotel-notes-board')

<!-- Header -->
<div class="flex items-start justify-between gap-3 mb-4 flex-wrap">
    <div class="min-w-0">
        {{--
            عدّادات النزلاء. "الإجمالي" يضمّ المغادرين تاريخياً فلا يُقرأ منه عدد
            من هم في الفندق الآن، ولهذا صار "الموجودون الآن" بطاقةً مستقلة بارزة
            بجواره، ومعهما من يُتوقَّع خروجهم اليوم ومن تأخّر.
        --}}
        <div id="countCards" class="flex items-stretch gap-2 flex-wrap"
             data-total="{{ $total }}" data-present="{{ $presentCount }}"
             data-today="{{ $todayCount }}" data-overdue="{{ $overdueCount }}"
             data-departed="{{ $departedCount }}" data-departed-today="{{ $departedTodayCount }}">
            <div class="rounded-xl border border-green-200 bg-green-50 px-3 py-2 min-w-[8.5rem]">
                <div class="text-xl font-black text-green-700 leading-none" data-count="present">{{ $presentCount }}</div>
                <div class="text-[11px] font-semibold text-green-700 mt-1">النزلاء الموجودون الآن</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white px-3 py-2 min-w-[8.5rem]">
                <div class="text-xl font-black leading-none" style="color:#0F4C75;" data-count="total">{{ $total }}</div>
                <div class="text-[11px] font-semibold text-gray-500 mt-1" data-count-label="total">إجمالي النزلاء (الكل)</div>
            </div>
            <div class="rounded-xl border border-orange-200 bg-orange-50 px-3 py-2 min-w-[8.5rem]">
                <div class="text-xl font-black text-orange-600 leading-none" data-count="today">{{ $todayCount }}</div>
                <div class="text-[11px] font-semibold text-orange-700 mt-1">خروجهم اليوم</div>
            </div>
            <div class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 min-w-[8.5rem]">
                <div class="text-xl font-black text-red-600 leading-none" data-count="overdue">{{ $overdueCount }}</div>
                <div class="text-[11px] font-semibold text-red-700 mt-1">متأخرون عن الخروج</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 min-w-[8.5rem]">
                <div class="text-xl font-black text-gray-600 leading-none" data-count="departed">{{ $departedCount }}</div>
                <div class="text-[11px] font-semibold text-gray-500 mt-1">
                    المغادرون <span class="text-gray-400">(اليوم: <span data-count="departed-today">{{ $departedTodayCount }}</span>)</span>
                </div>
            </div>
        </div>
    </div>
    <div class="flex items-center gap-2">
        {{-- طيّ نصوص الملاحظات: لمن يريد قائمةً نظيفة بالأيقونات وحدها --}}
        <button type="button" data-notes-text-toggle onclick="toggleNotesText()"
                class="flex items-center gap-1.5 px-3 py-2 border border-gray-300 text-gray-600 rounded-lg text-xs hover:bg-gray-50 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            <span data-notes-text-label>إخفاء نصوص الملاحظات</span>
        </button>
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            لوحة التحكم
        </a>
    </div>
</div>

<!-- Filters -->
<form method="GET" action="{{ route('reservations.expiring') }}" id="filters" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <div class="flex flex-wrap gap-3 items-end">

        {{-- Search: name or room --}}
        <div class="flex flex-col gap-1 flex-1 min-w-48">
            <label class="text-xs font-medium text-gray-500">بحث باسم النزيل أو رقم الغرفة</label>
            <div class="relative">
                <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input type="text" name="search" id="searchInput" value="{{ request('search') }}"
                       placeholder="اسم النزيل أو رقم الغرفة..."
                       oninput="debounceSearch(this)"
                       class="w-full border border-gray-200 rounded-lg pr-9 pl-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
            </div>
        </div>

        {{-- Check-in date --}}
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">تاريخ الدخول</label>
            <input type="date" name="check_in_date" value="{{ request('check_in_date') }}"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
        </div>

        {{-- Check-out date --}}
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">تاريخ المغادرة</label>
            <input type="date" name="check_out_date" value="{{ request('check_out_date') }}"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
        </div>

        {{-- Status --}}
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">الحالة</label>
            <select name="status"
                     class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
                <option value="all"         {{ ($status ?? 'all') === 'all'         ? 'selected' : '' }}>الكل</option>
                <option value="checked_in"  {{ ($status ?? '') === 'checked_in'  ? 'selected' : '' }}>المقيمون حالياً</option>
                <option value="checked_out" {{ ($status ?? '') === 'checked_out' ? 'selected' : '' }}>المغادرون</option>
            </select>
        </div>

        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm font-medium self-end" style="background:#0F4C75;">بحث</button>

        {{-- يُعرَض/يُخفى عبر JS مع كل فلترة حيّة (لا يُعاد رسمه بإعادة تحميل) --}}
        <a href="{{ route('reservations.expiring') }}" id="clearFilters"
           class="inline-flex items-center gap-1.5 px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition self-end
                  {{ (request()->hasAny(['search','check_in_date','check_out_date']) || (request('status') && request('status') !== 'all')) ? '' : 'hidden' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            إلغاء الفلترة
        </a>
    </div>
</form>

<!-- Table -->
<div id="resultsArea">
    @include('reservations._expiring_results')
</div>

</div>

@push('scripts')
@include('reservations._quick_actions_js')
<script>
(function () {
    const form    = document.getElementById('filters');
    const results = document.getElementById('resultsArea');
    const cards   = document.getElementById('countCards');
    if (!form || !results) return;

    let timer = null;
    let seq   = 0;   // يمنع سباق الطلبات: نتجاهل رد طلب قديم وصل متأخراً

    // فلترة حيّة دون إعادة تحميل الصفحة — نستبدل جزء النتائج فقط، فيبقى
    // تركيز حقل البحث ونصّه كما هما ويواصل الموظف الكتابة بلا انقطاع.
    async function load(url, push = true) {
        const mySeq = ++seq;
        results.style.opacity = '0.55';
        try {
            const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const html = await res.text();
            if (mySeq !== seq) return;   // وصل رد أحدث بالفعل — نتجاهل هذا
            results.innerHTML = html;
            updateSummary();
            if (push) history.replaceState(null, '', url);
        } catch (e) {
            // فشل الشبكة: نُبقي النتائج الحالية كما هي بدل إفراغ الجدول
        } finally {
            if (mySeq === seq) results.style.opacity = '1';
        }
    }

    function runFilter() {
        const params = new URLSearchParams(new FormData(form)).toString();
        load(form.action + (params ? '?' + params : ''));
        toggleClearButton();
    }

    // زر "إلغاء الفلترة" خارج منطقة النتائج المُستبدَلة، فنُظهره/نُخفيه هنا
    function toggleClearButton() {
        const btn = document.getElementById('clearFilters');
        if (!btn) return;
        const d = new FormData(form);
        const active = ['search', 'check_in_date', 'check_out_date'].some(k => (d.get(k) || '').trim() !== '')
                     || ((d.get('status') || 'all') !== 'all');
        btn.classList.toggle('hidden', !active);
    }

    // أرقام العدّادات تصل ضمن جزئية النتائج (data-*) فنحدّث بها البطاقات
    function updateSummary() {
        const box = results.firstElementChild;
        if (!box || !cards) return;

        const set = (key, value) => {
            const el = cards.querySelector(`[data-count="${key}"]`);
            if (el) el.textContent = value ?? '0';
        };

        set('present',        box.dataset.present);
        set('total',          box.dataset.total);
        set('today',          box.dataset.today);
        set('overdue',        box.dataset.overdue);
        set('departed',       box.dataset.departed);
        set('departed-today', box.dataset.departedToday);

        // عنوان الإجمالي يتبع الفلتر: "الكل" يضمّ المغادرين، وغيره لا
        const status = form.querySelector('[name="status"]')?.value || 'all';
        const label  = cards.querySelector('[data-count-label="total"]');
        if (label) {
            label.textContent = status === 'checked_out' ? 'إجمالي المغادرين'
                              : status === 'checked_in'  ? 'إجمالي المقيمين'
                              : 'إجمالي النزلاء (الكل)';
        }

        // نصوص الملاحظات تُرسَم من جديد مع النتائج، فتُعاد حالة الطيّ عليها
        if (window.applyNotesTextState) window.applyNotesTextState();
    }

    // البحث النصّي: مهلة قصيرة فقط لتجميع الأحرف المتتابعة (لا إعادة تحميل)
    window.debounceSearch = function () {
        clearTimeout(timer);
        timer = setTimeout(runFilter, 300);
    };

    // بقية الفلاتر (التواريخ/الحالة) تُطبَّق فوراً بنفس الآلية
    form.querySelectorAll('input[type="date"], select').forEach(el => {
        el.addEventListener('change', () => { clearTimeout(timer); runFilter(); });
    });

    form.addEventListener('submit', e => { e.preventDefault(); clearTimeout(timer); runFilter(); });

    // ترقيم الصفحات داخل النتائج يعمل أيضاً دون إعادة تحميل
    results.addEventListener('click', e => {
        const link = e.target.closest('a[href]');
        if (!link || !link.href.includes('page=')) return;
        e.preventDefault();
        load(link.href);
    });
})();
</script>
@endpush
@include('reservations._quick_actions')
@endsection
