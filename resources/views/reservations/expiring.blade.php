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

<!-- Header -->
<div class="flex items-center justify-between mb-4">
    <div>
        <p class="text-sm text-gray-500">
            @php $st = $status ?? 'all'; @endphp
            <span id="resultsLabel">{{ $st === 'checked_out' ? 'إجمالي المغادرين' : ($st === 'all' ? 'إجمالي النزلاء' : 'إجمالي المسجلين') }}</span>:
            <span id="resultsSummary" data-show-badges="{{ $st !== 'checked_out' ? '1' : '0' }}">
                <strong>{{ $total }}</strong>
                @if($st !== 'checked_out')
                @if($overdueCount > 0)
                — <span class="text-red-600 font-semibold">{{ $overdueCount }} متأخر</span>
                @endif
                @if($todayCount > 0)
                — <span class="text-orange-600 font-semibold">{{ $todayCount }} خروجهم اليوم</span>
                @endif
                @endif
            </span>
        </p>
    </div>
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        لوحة التحكم
    </a>
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
<script>
(function () {
    const form    = document.getElementById('filters');
    const results = document.getElementById('resultsArea');
    const summary = document.getElementById('resultsSummary');
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

    // أرقام العدّادات تصل ضمن جزئية النتائج (data-*) فنحدّث بها سطر الملخص
    function updateSummary() {
        const box = results.firstElementChild;
        if (!box || !summary) return;

        const status  = form.querySelector('[name="status"]')?.value || 'all';
        const total   = box.dataset.total ?? '0';
        const overdue = parseInt(box.dataset.overdue ?? '0', 10);
        const today   = parseInt(box.dataset.today   ?? '0', 10);

        const label = document.getElementById('resultsLabel');
        if (label) {
            label.textContent = status === 'checked_out' ? 'إجمالي المغادرين'
                              : status === 'all'         ? 'إجمالي النزلاء'
                              : 'إجمالي المسجلين';
        }

        let html = `<strong>${total}</strong>`;
        if (status !== 'checked_out') {
            if (overdue > 0) html += ` — <span class="text-red-600 font-semibold">${overdue} متأخر</span>`;
            if (today > 0)   html += ` — <span class="text-orange-600 font-semibold">${today} خروجهم اليوم</span>`;
        }
        summary.innerHTML = html;
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
@endsection
