@extends('layouts.app')
@section('title', 'دليل الحسابات')
@section('page-title', 'دليل الحسابات')

@push('styles')
<style>
/*
    دليل الحسابات — لوحة عمل لا صفحة عرض.

    الفكرة: الشاشة أداة محاسب، فالكثافة فضيلة والزينة عبء. كل قرار هنا يخدم
    المسح البصري السريع لمئتَي سطر: الأرقام بخط جدولي ثابت العرض تصطفّ خانةً
    فوق خانة، والألوان تحمل معنى واحداً (نوع الحساب) لا تُزيّن، والأوامر
    تختفي حتى يُطلب السطر. اللوحتان تمرّان مستقلتين والصفحة نفسها لا تمرّ،
    فلا يضيع موضع القراءة عند فتح حساب.

    الألوان متغيرات تُقلب كتلةً واحدة في الوضع الليلي، فلا تتناثر قيم ثابتة.
*/
.coa {
    /* أسماء مستعارة لتوكينات نظام الواجهة المشترك (ui-kit.css) — مصدر واحد
       للألوان في كل الشاشات، وقلبٌ واحد للوضع الليلي بدل نسخة لكل صفحة */
    --coa-bg:        var(--ui-bg);
    --coa-bg-sunken: var(--ui-sunken);
    --coa-locked:    var(--ui-locked);
    --coa-line:      var(--ui-line);
    --coa-line-soft: var(--ui-line-soft);
    --coa-ink:       var(--ui-ink);
    --coa-ink-2:     var(--ui-ink-2);
    --coa-ink-3:     var(--ui-ink-3);
    --coa-accent:    var(--ui-accent);
    --coa-accent-bg: var(--ui-accent-bg);
    --coa-negative:  var(--ui-neg);
    --coa-hover:     var(--ui-hover);

    --coa-asset:     var(--ui-asset);
    --coa-liability: var(--ui-liability);
    --coa-equity:    var(--ui-equity);
    --coa-revenue:   var(--ui-revenue);
    --coa-expense:   var(--ui-expense);

    --coa-mono: var(--ui-mono);
}

/* ── هيكل الصفحة ─────────────────────────────────────────────── */
.coa { display: flex; flex-direction: column; gap: 0; color: var(--coa-ink); }

@media (min-width: 1024px) {
    /* الصفحة لا تمرّ؛ اللوحتان تمرّان — فلا يضيع موضع القراءة عند فتح حساب */
    .coa { height: calc(100dvh - 8.5rem); }
}

.coa-head {
    display: flex; align-items: baseline; gap: .75rem; flex-wrap: wrap;
    padding-bottom: .75rem;
}
.coa-title { font-size: 1.05rem; font-weight: 700; letter-spacing: -.01em; }
.coa-meta  { font-size: .75rem; color: var(--coa-ink-2); }
.coa-meta b { font-variant-numeric: tabular-nums; font-weight: 700; color: var(--coa-ink); }
.coa-head-actions { margin-inline-start: auto; display: flex; gap: .5rem; align-items: center; }

.coa-btn {
    display: inline-flex; align-items: center; gap: .375rem; white-space: nowrap;
    height: 2.125rem; padding: 0 .875rem; border-radius: .5rem;
    font-size: .8125rem; font-weight: 600; border: 1px solid var(--coa-line);
    background: var(--coa-bg); color: var(--coa-ink-2); transition: all .12s;
}
.coa-btn:hover { border-color: var(--coa-accent); color: var(--coa-accent); }
.coa-btn--primary {
    background: var(--coa-accent); border-color: var(--coa-accent); color: var(--ui-accent-fg);
}
.coa-btn--primary:hover { filter: brightness(1.12); color: var(--ui-accent-fg); }
.coa-btn--danger { color: var(--coa-negative); }
.coa-btn--danger:hover { border-color: var(--coa-negative); color: var(--coa-negative); }
.coa-btn[disabled], .coa-btn.is-off {
    opacity: 1; pointer-events: none;
    background: var(--coa-locked); border-color: transparent; color: var(--coa-ink-3);
}
.coa-btn.is-off:hover { color: var(--coa-ink-3); }

/* ── شريط البحث والفلاتر ─────────────────────────────────────── */
.coa-bar {
    display: flex; align-items: center; gap: .5rem; flex-wrap: wrap;
    padding: .5rem .625rem; border: 1px solid var(--coa-line);
    border-radius: .625rem; background: var(--coa-bg); margin-bottom: .75rem;
}
.coa-search { position: relative; flex: 1 1 15rem; min-width: 0; }
.coa-search input {
    width: 100%; height: 2.125rem; padding: 0 2.25rem 0 3.25rem;
    border: 1px solid var(--coa-line); border-radius: .5rem;
    background: var(--coa-bg-sunken); color: var(--coa-ink);
    font-size: .8125rem; outline: none; transition: border-color .12s, background .12s;
}
.coa-search input:focus { border-color: var(--coa-accent); background: var(--coa-bg); }
.coa-search input::placeholder { color: var(--coa-ink-3); }
.coa-search svg {
    position: absolute; inset-inline-start: .625rem; top: 50%; transform: translateY(-50%);
    width: 1rem; height: 1rem; color: var(--coa-ink-3); pointer-events: none;
}
/* مفتاح الاختصار يُعلن عن نفسه بدل أن يبقى سرّاً */
.coa-kbd {
    position: absolute; inset-inline-end: .5rem; top: 50%; transform: translateY(-50%);
    font-family: var(--coa-mono); font-size: .6875rem; line-height: 1;
    padding: .1875rem .375rem; border-radius: .25rem;
    border: 1px solid var(--coa-line); color: var(--coa-ink-3); background: var(--coa-bg);
}
.coa-hits {
    position: absolute; inset-inline-end: .5rem; top: 50%; transform: translateY(-50%);
    font-size: .6875rem; font-weight: 600; color: var(--coa-accent);
    font-variant-numeric: tabular-nums;
}
.coa-bar select {
    height: 2.125rem; padding: 0 .5rem; border: 1px solid var(--coa-line);
    border-radius: .5rem; background: var(--coa-bg); color: var(--coa-ink);
    font-size: .75rem; outline: none;
}
.coa-bar select:focus { border-color: var(--coa-accent); }
.coa-toggle {
    display: inline-flex; align-items: center; gap: .375rem; cursor: pointer;
    height: 2.125rem; padding: 0 .625rem; border-radius: .5rem;
    border: 1px solid var(--coa-line); font-size: .75rem; color: var(--coa-ink-2);
    white-space: nowrap; user-select: none;
}
.coa-toggle:hover { border-color: var(--coa-accent); }
.coa-toggle input { accent-color: var(--coa-accent); }
.coa-toggle:has(input:checked) {
    background: var(--coa-accent-bg); border-color: var(--coa-accent); color: var(--coa-accent);
}

/* ── اللوحتان ────────────────────────────────────────────────── */
.coa-panes { display: grid; gap: .75rem; min-height: 0; grid-template-columns: 1fr; }
@media (min-width: 1024px) {
    .coa-panes { grid-template-columns: minmax(0,1fr) 25rem; }
}
.coa-pane {
    border: 1px solid var(--coa-line); border-radius: .625rem;
    background: var(--coa-bg); display: flex; flex-direction: column;
    min-height: 0; overflow: hidden;
}
.coa-pane-head {
    display: flex; align-items: center; gap: .5rem;
    padding: .5rem .75rem; border-bottom: 1px solid var(--coa-line);
    background: var(--coa-bg-sunken); flex-shrink: 0;
}
.coa-pane-title {
    font-size: .75rem; font-weight: 700; color: var(--coa-ink-2);
}
.coa-pane-body { overflow: auto; flex: 1 1 auto; min-height: 0; -webkit-overflow-scrolling: touch; }
@media (max-width: 1023px) { .coa-pane-body { max-height: 65vh; } }

.coa-mini {
    background: none; border: 0; cursor: pointer; padding: .25rem .5rem;
    font-size: .6875rem; font-weight: 600; color: var(--coa-ink-2); border-radius: .375rem;
}
.coa-mini:hover { background: var(--coa-hover); color: var(--coa-accent); }

/* ترويسة أعمدة الشجرة — تُعرّف عمود الرصيد قبل أن يبدأ المسح */
.coa-colhead {
    display: flex; padding: .3125rem .75rem; gap: .5rem;
    border-bottom: 1px solid var(--coa-line-soft); background: var(--coa-bg);
    font-size: .6875rem; font-weight: 700; color: var(--coa-ink-3);
    position: sticky; top: 0; z-index: 2;
}
.coa-colhead span:last-child { margin-inline-start: auto; }

/* ── السطر ───────────────────────────────────────────────────── */
.coa-tree, .coa-children { list-style: none; margin: 0; padding: 0; }

/* خط إرشاد رفيع يربط الفروع بأبيها — بدونه تبدو المستويات الأربعة مستوىً واحداً */
.coa-children { position: relative; }
.coa-children::before {
    content: ''; position: absolute; inset-block: 0; top: 0; bottom: 0;
    inset-inline-start: calc(1.0625rem + var(--guide, 0rem));
    border-inline-start: 1px solid var(--coa-line);
}

.coa-row {
    display: flex; align-items: center; gap: .375rem;
    padding: .25rem .75rem;
    padding-inline-start: calc(.5rem + var(--depth) * 1.375rem);
    border-bottom: 1px solid var(--coa-line-soft);
    font-size: .8125rem; line-height: 1.5; position: relative;
    transition: background .1s;
}
.coa-row:hover { background: var(--coa-hover); }
.coa-row.is-selected { background: var(--coa-accent-bg); }
.coa-row.is-selected::before {
    content: ''; position: absolute; inset-block: 0; inset-inline-start: 0;
    width: 3px; background: var(--coa-accent);
}
.coa-row.is-suspended .coa-code,
.coa-row.is-suspended .coa-name { opacity: .5; }

.coa-twisty {
    flex: 0 0 auto; width: 1.125rem; height: 1.125rem; display: grid; place-items: center;
    border: 0; background: none; cursor: pointer; color: var(--coa-ink-3); border-radius: .25rem;
}
.coa-twisty:hover { color: var(--coa-accent); background: var(--coa-bg-sunken); }
.coa-twisty svg { width: .875rem; height: .875rem; transition: transform .15s ease; }
.coa-twisty svg.is-open { transform: rotate(-90deg); }
.coa-twisty--leaf { cursor: default; position: relative; }
.coa-twisty--leaf::after {
    content: ''; width: 3px; height: 3px; border-radius: 50%;
    background: var(--coa-line); display: block;
}

/* الكود يحمل نوع الحساب بلونه — فلا حاجة لشارة نصّية على كل سطر */
.coa-code {
    flex: 0 0 auto; font-family: var(--coa-mono); font-size: .75rem; font-weight: 600;
    font-variant-numeric: tabular-nums; letter-spacing: .01em; color: var(--coa-ink-2);
}
.coa-row--t-asset     .coa-code { color: var(--coa-asset); }
.coa-row--t-liability .coa-code { color: var(--coa-liability); }
.coa-row--t-equity    .coa-code { color: var(--coa-equity); }
.coa-row--t-revenue   .coa-code { color: var(--coa-revenue); }
.coa-row--t-expense   .coa-code { color: var(--coa-expense); }

.coa-lock { flex: 0 0 auto; width: .6875rem; height: .6875rem; color: var(--coa-ink-3); }

.coa-name {
    color: var(--coa-ink); text-decoration: none; min-width: 0; flex: 0 1 auto;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
a.coa-name:hover { color: var(--coa-accent); text-decoration: underline; text-underline-offset: 2px; }
.coa-row[style*="--depth: 0"] .coa-name,
.coa-row[style*="--depth: 1"] .coa-name { font-weight: 700; }

.coa-name-en {
    font-size: .6875rem; color: var(--coa-ink-3); white-space: nowrap; flex: 0 1 auto;
    overflow: hidden; text-overflow: ellipsis; max-width: 11rem;
}
@media (max-width: 1400px) { .coa-name-en { display: none; } }

.coa-flag {
    flex: 0 0 auto; font-size: .625rem; font-weight: 700; padding: .0625rem .375rem;
    border-radius: 999px; color: var(--coa-negative);
    border: 1px solid currentColor; opacity: .8;
}

/* الأوامر صامتة حتى يُطلب السطر */
.coa-add {
    flex: 0 0 auto; width: 1.25rem; height: 1.25rem; display: grid; place-items: center;
    border-radius: .3125rem; border: 1px dashed var(--coa-line);
    color: var(--coa-ink-3); font-size: .875rem; line-height: 1; text-decoration: none;
    opacity: 0; transition: opacity .1s, color .1s, border-color .1s;
}
.coa-row:hover .coa-add, .coa-row.is-selected .coa-add, .coa-add:focus-visible { opacity: 1; }
.coa-add:hover { color: var(--coa-accent); border-color: var(--coa-accent); border-style: solid; }

/* الرصيد: عمود واحد، خط جدولي، والسالب بين قوسين كما في الدفاتر */
.coa-amount {
    margin-inline-start: auto; flex: 0 0 auto; text-align: end;
    font-family: var(--coa-mono); font-size: .75rem; font-variant-numeric: tabular-nums;
    color: var(--coa-ink); min-width: 8rem; padding-inline-start: .75rem;
}
.coa-amount.is-rollup  { color: var(--coa-ink-2); font-weight: 400; }
.coa-amount.is-negative { color: var(--coa-negative); }
.coa-zero { color: var(--coa-ink-3); }

.coa-empty { padding: 3rem 1rem; text-align: center; font-size: .8125rem; color: var(--coa-ink-3); }

/* ── لوحة التفاصيل ───────────────────────────────────────────── */
.coa-detail { display: flex; flex-direction: column; min-height: 0; }
.coa-detail-scroll { overflow: auto; flex: 1 1 auto; min-height: 0; }

/* على الجوال تُصبح اللوحة ورقةً منزلقة فوق الشجرة بدل أن تُدفن تحتها */
@media (max-width: 1023px) {
    .coa-pane--detail.has-content {
        position: fixed; inset: auto 0 0 0; z-index: 45;
        max-height: 88dvh; border-radius: .875rem .875rem 0 0;
        box-shadow: 0 -12px 32px rgba(15, 23, 42, .18);
        animation: coa-sheet .18s ease-out;
    }
    html.dark .coa-pane--detail.has-content { box-shadow: 0 -12px 32px rgba(0, 0, 0, .5); }
    .coa-pane--detail:not(.has-content) { display: none; }

    /* مقبض يُعرّف اللوحة ورقةً منزلقة لا جزءاً من الصفحة */
    .coa-pane--detail.has-content::before {
        content: ''; position: absolute; top: .375rem; inset-inline: 0; margin: 0 auto;
        width: 2.25rem; height: .1875rem; border-radius: 999px; background: var(--coa-line);
    }
    .coa-pane--detail.has-content .coa-pane-head { padding-top: .875rem; }
}
@keyframes coa-sheet { from { transform: translateY(1.5rem); opacity: .6; } }

.coa-ident {
    display: grid; grid-template-columns: repeat(2, minmax(0,1fr));
    gap: .0625rem; background: var(--coa-line-soft);
    border-block: 1px solid var(--coa-line);
}
.coa-ident div { background: var(--coa-bg); padding: .5rem .75rem; }
.coa-ident dt { font-size: .625rem; color: var(--coa-ink-3); margin-bottom: .125rem; }
.coa-ident dd { font-size: .75rem; font-weight: 600; color: var(--coa-ink); }

.coa-field { padding: 0 .875rem .75rem; }
.coa-field label {
    display: block; font-size: .6875rem; font-weight: 600;
    color: var(--coa-ink-2); margin-bottom: .25rem;
}
.coa-field .req { color: var(--coa-negative); }
.coa-input {
    width: 100%; height: 2.25rem; padding: 0 .625rem;
    border: 1px solid var(--coa-line); border-radius: .5rem;
    background: var(--coa-bg); color: var(--coa-ink); font-size: .8125rem; outline: none;
    transition: border-color .12s;
}
.coa-input:focus { border-color: var(--coa-accent); }
/*
    !important مقصود: app-theme.css يفرض خلفية الحقول عبر محدّد أقوى
    (html:not(.dark) input:not(...)) فيبتلع أي تمييز للحقل المقروء. وبدون هذا
    التمييز يبدو الحقل المقفل قابلاً للكتابة، فيكتب المستخدم ثم يُفاجأ بالرفض.
*/
.coa-input[readonly], .coa-input:disabled {
    background: var(--coa-locked) !important;
    color: var(--coa-ink-2) !important;
    cursor: default;
    box-shadow: none;
}
.coa-input.is-mono { font-family: var(--coa-mono); font-variant-numeric: tabular-nums; }
.coa-hint { font-size: .6875rem; color: var(--coa-ink-3); margin-top: .25rem; }

.coa-section {
    padding: .625rem .875rem .375rem; font-size: .75rem; font-weight: 700;
    color: var(--coa-ink-2);
}
.coa-note {
    margin: 0 .875rem .75rem; padding: .5rem .625rem; border-radius: .5rem;
    font-size: .6875rem; line-height: 1.6;
    border: 1px solid var(--coa-line); background: var(--coa-bg-sunken); color: var(--coa-ink-2);
}
.coa-note--warn  { border-color: var(--ui-warn-line); background: var(--ui-warn-bg); color: var(--ui-warn); }
.coa-note--error { border-color: var(--ui-err-line);  background: var(--ui-err-bg);  color: var(--ui-neg); }

.coa-actions {
    display: flex; gap: .5rem; align-items: center; flex-shrink: 0;
    padding: .625rem .875rem; border-top: 1px solid var(--coa-line); background: var(--coa-bg-sunken);
}
</style>
@endpush

@section('content')
<div class="coa" dir="rtl" x-data="coaWorkspace()" x-init="init()">

    {{-- ترويسة --}}
    <div class="coa-head">
        <h2 class="coa-title">دليل الحسابات</h2>
        <p class="coa-meta">
            <b>{{ number_format($totals['all']) }}</b> حساباً،
            منها <b>{{ number_format($totals['posting']) }}</b> قابل للترحيل
            · معيار USALI · {{ config('hotel.base_currency') }}
        </p>

        <div class="coa-head-actions">
            @if($canManage)
            <a href="{{ route('coa.index', ['new' => 1]) }}#coa-detail" class="coa-btn coa-btn--primary" data-coa-open>
                <span aria-hidden="true">+</span> حساب جديد
            </a>
            @endif
            <a href="{{ route('coa.tree', request()->query()) }}" target="_blank" class="coa-btn">تصدير JSON</a>
        </div>
    </div>

    {{-- بحث وفلاتر في شريط واحد: البحث فوريّ في المتصفح، والفلاتر تُعيد البناء
         من الخادم لأنها تغيّر مجموعة الحسابات نفسها لا عرضها --}}
    <form method="GET" action="{{ route('coa.index') }}" class="coa-bar" x-ref="filters">
        <label class="coa-search">
            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.8"/>
                <path d="M13.5 13.5L17 17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            <input type="search" x-ref="search" x-model="q" @input="filter()"
                   @keydown.escape.prevent="q=''; filter(); $refs.search.blur()"
                   placeholder="ابحث بالكود أو الاسم…" autocomplete="off" aria-label="بحث في الحسابات">
            <span class="coa-kbd" x-show="!q" aria-hidden="true">/</span>
            <span class="coa-hits" x-show="q" x-cloak x-text="hits + ' نتيجة'"></span>
        </label>

        <select name="type" onchange="this.form.submit()" aria-label="نوع الحساب">
            <option value="">كل الأنواع</option>
            @foreach($types as $type)
            <option value="{{ $type }}" @selected(($filters['type'] ?? null) === $type)>
                {{ ['asset'=>'أصول','liability'=>'خصوم','equity'=>'حقوق ملكية','revenue'=>'إيرادات','expense'=>'مصروفات'][$type] ?? $type }}
            </option>
            @endforeach
        </select>

        <select name="department" onchange="this.form.submit()" aria-label="القسم">
            <option value="">كل الأقسام</option>
            @foreach($departments as $dept)
            <option value="{{ $dept }}" @selected(($filters['department'] ?? null) === $dept)>
                {{ ['rooms'=>'الغرف','fnb'=>'الأطعمة والمشروبات','spa'=>'المنتجع','laundry'=>'المغسلة','parking'=>'المواقف','admin'=>'الإدارة','sales'=>'المبيعات والتسويق','maintenance'=>'الصيانة','utilities'=>'المرافق'][$dept] ?? $dept }}
            </option>
            @endforeach
        </select>

        <label class="coa-toggle">
            <input type="checkbox" name="posting_only" value="1" onchange="this.form.submit()"
                   @checked($filters['posting_only'] ?? false)>
            القابلة للترحيل
        </label>

        {{-- بدون هذا الخيار يختفي الحساب فور إيقافه فلا يبقى سبيل لإعادته --}}
        <label class="coa-toggle">
            <input type="checkbox" name="only_active" value="0" onchange="this.form.submit()"
                   @checked(!($filters['only_active'] ?? true))>
            إظهار الموقوفة
        </label>

        @if(($filters['type'] ?? null) || ($filters['department'] ?? null) || ($filters['posting_only'] ?? false) || !($filters['only_active'] ?? true))
        <a href="{{ route('coa.index') }}" class="coa-btn">مسح الفلاتر</a>
        @endif
    </form>

    {{-- اللوحتان --}}
    <div class="coa-panes">

        {{-- الشجرة --}}
        <section class="coa-pane" aria-label="شجرة الحسابات">
            <div class="coa-pane-head">
                <span class="coa-pane-title">الشجرة</span>
                <button type="button" class="coa-mini" @click="setAll(true)">توسيع الكل</button>
                <button type="button" class="coa-mini" @click="setAll(false)">طيّ الكل</button>
            </div>

            @if(empty($tree))
            <p class="coa-empty">لا توجد حسابات مطابقة للفلترة.</p>
            @else
            <div class="coa-pane-body">
                <div class="coa-colhead">
                    <span>الحساب</span>
                    <span>الرصيد ({{ config('hotel.base_currency') }})</span>
                </div>
                <ul class="coa-tree" x-ref="tree">
                    @foreach($tree as $node)
                        @include('partials.coa-node', [
                            'node'         => $node,
                            'canManage'    => $canManage,
                            'selectedCode' => $editing?->code,
                        ])
                    @endforeach
                </ul>
                <p class="coa-empty" x-show="q && hits === 0" x-cloak>لا نتائج لـ «<span x-text="q"></span>»</p>
            </div>
            @endif
        </section>

        {{-- التفاصيل والتحرير --}}
        @if($canManage)
        <section id="coa-detail"
                 class="coa-pane coa-pane--detail {{ ($editing || $creating) ? 'has-content' : '' }}"
                 aria-label="بيانات الحساب">
            @include('accounting._account-form')
        </section>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function coaWorkspace() {
    return {
        q: '',
        hits: 0,

        init() {
            // «/» يُركّز البحث كما في أدوات المحاسبة المكتبية
            document.addEventListener('keydown', (e) => {
                const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement?.tagName);
                if (e.key === '/' && !typing) { e.preventDefault(); this.$refs.search?.focus(); }
            });

            this.enhanceLinks();

            // الحساب المفتوح قد يقع خارج نافذة الشجرة بعد فتح مساره، فيبدو أن
            // شيئاً لم يُختر. نُحضره إلى وسط اللوحة بعد أن تستقرّ الفروع.
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.$el.querySelector('.coa-row.is-selected')
                        ?.scrollIntoView({ block: 'center' });
                });
            });
        },

        nodes() {
            return Array.from(this.$refs.tree?.querySelectorAll('.coa-node') ?? []);
        },

        setAll(open) {
            this.nodes().forEach(el => {
                const data = Alpine.$data(el);
                if (data && typeof data.open !== 'undefined') data.open = open;
            });
        },

        /**
         * بحث فوريّ في المتصفح: نُخفي غير المطابق ونُبقي آباء المطابق ظاهرين
         * ومفتوحين، وإلا انقطع المسار من الجذر إلى النتيجة فبدت معلّقة.
         */
        filter() {
            const term  = this.q.trim().toLowerCase();
            const nodes = this.nodes();

            if (!term) {
                nodes.forEach(el => { el.style.display = ''; });
                this.setAll(false);
                nodes.filter(el => el.parentElement?.classList.contains('coa-tree'))
                     .forEach(el => { const d = Alpine.$data(el); if (d) d.open = true; });
                this.hits = 0;
                return;
            }

            const keep = new Set();
            let matched = 0;

            nodes.forEach(el => {
                if (!(el.dataset.search || '').includes(term)) return;
                matched++;
                let cur = el;
                while (cur) {
                    keep.add(cur);
                    cur = cur.parentElement?.closest('.coa-node');
                }
                // وفروع المطابق تظهر معه ليُقرأ في سياقه
                el.querySelectorAll('.coa-node').forEach(child => keep.add(child));
            });

            nodes.forEach(el => {
                const visible = keep.has(el);
                el.style.display = visible ? '' : 'none';
                const d = Alpine.$data(el);
                if (d && visible) d.open = true;
            });

            this.hits = matched;
        },

        /**
         * فتح حساب يستبدل لوحة التفاصيل وحدها بدل إعادة تحميل الصفحة، فلا يضيع
         * موضع القراءة في شجرة من مئتَي سطر. وإن تعذّر الجلب يتبع المتصفح
         * الرابط كالمعتاد — فالصفحة تعمل كاملةً بلا جافاسكربت.
         */
        enhanceLinks() {
            document.addEventListener('click', async (e) => {
                const link = e.target.closest('a[data-coa-open]');
                if (!link || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;

                e.preventDefault();
                const panel = document.getElementById('coa-detail');
                if (!panel) { window.location.href = link.href; return; }

                panel.style.opacity = '.5';
                try {
                    const html = await (await fetch(link.href, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    })).text();

                    const fresh = new DOMParser()
                        .parseFromString(html, 'text/html')
                        .getElementById('coa-detail');
                    if (!fresh) throw new Error('no panel');

                    panel.innerHTML = fresh.innerHTML;
                    panel.className = fresh.className;
                    history.pushState({}, '', link.href);
                    this.markSelected(new URL(link.href).searchParams.get('edit'));
                    if (window.innerWidth < 1024) panel.scrollIntoView({ block: 'nearest' });
                } catch {
                    window.location.href = link.href;
                } finally {
                    panel.style.opacity = '';
                }
            });
        },

        markSelected(code) {
            this.nodes().forEach(el => {
                el.querySelector('.coa-row')?.classList.toggle('is-selected', el.dataset.code === code);
            });
        },
    };
}
</script>
@endpush
