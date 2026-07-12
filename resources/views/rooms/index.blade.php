@extends('layouts.app')
@section('title', 'الغرف')
@section('page-title', 'إدارة الغرف')

@push('styles')
<style>
.room-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.07), 0 1px 2px rgba(0,0,0,0.05);
    border: 1.5px solid #f0f2f5;
    transition: box-shadow 0.2s, transform 0.15s, border-color 0.2s;
    position: relative;
    overflow: hidden;
}
.room-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.10); transform: translateY(-2px); }
.room-card.selected { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.18); }
.room-card .status-bar { height: 4px; width: 100%; display: block; }
.status-bar-available    { background: linear-gradient(90deg,#10b981,#34d399); }
.status-bar-occupied     { background: linear-gradient(90deg,#ef4444,#f87171); }
.status-bar-inspection   { background: linear-gradient(90deg,#f59e0b,#fbbf24); }
.status-bar-maintenance  { background: linear-gradient(90deg,#9ca3af,#d1d5db); }

.stat-chip {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 16px; border-radius: 12px;
    cursor: pointer; transition: all 0.18s;
    border: 1.5px solid transparent;
    text-decoration: none;
}
.stat-chip:hover { transform: translateY(-1px); }

.filter-chip {
    padding: 6px 14px; border-radius: 20px; font-size: 0.75rem;
    font-weight: 600; cursor: pointer; transition: all 0.15s;
    border: 1.5px solid transparent; white-space: nowrap;
}

.room-action-btn {
    flex: 1; text-align: center; padding: 7px 0;
    font-size: 0.75rem; font-weight: 600;
    transition: background 0.15s; border: none; background: none;
    cursor: pointer;
}

/* ════════ الوضع الليلي ════════ */
html.dark .room-card {
    background: #1e293b;
    border-color: #2f3e54;
    box-shadow: 0 1px 3px rgba(0,0,0,.4), 0 4px 16px rgba(0,0,0,.25);
}
html.dark .room-card:hover { box-shadow: 0 6px 22px rgba(0,0,0,.5); }
html.dark .room-card.selected { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.28); }
/* رقم الغرفة + النصوص الداكنة داخل البطاقة */
html.dark .room-card .text-gray-800 { color: #f1f5f9 !important; }
html.dark .room-card .text-gray-600 { color: #cbd5e1 !important; }
html.dark .room-card .text-gray-400 { color: #94a3b8 !important; }
/* السعر (كان كحلي غامق غير مقروء على خلفية داكنة) */
html.dark .room-card [style*="color:#0F4C75"],
html.dark .room-card [style*="color: #0F4C75"] { color: #7ab3e0 !important; }
/* شريط الأدوات السفلي + الفواصل */
html.dark .room-card .border-gray-100,
html.dark .room-card.divide-gray-100 > :not([hidden]) ~ :not([hidden]) { border-color: #2f3e54 !important; }
html.dark .room-action-btn[style*="0F4C75"] { color: #7ab3e0 !important; }
/* رقائق الإحصاء والفلاتر */
html.dark .stat-chip.bg-white { background: #1e293b !important; }
html.dark .filter-chip.bg-white { background: #1e293b !important; }
</style>
@endpush

@section('content')
@php
    // عدّادات إجمالية مستقلة عن الفلاتر (تأتي من الكنترولر)
    $available   = (int) ($statusCounts['available'] ?? 0);
    $occupied    = (int) ($statusCounts['occupied'] ?? 0);
    $inspection  = (int) ($statusCounts['under_inspection'] ?? 0);
    $maintenance = (int) ($statusCounts['maintenance'] ?? 0);
    // "الإجمالي" هنا = الغرف التشغيلية (متاحة/مشغولة/تحت الفحص) دون الصيانة
    $total       = $available + $occupied + $inspection;

    $subBadges = [
        'double'    => ['label'=>'زوجية',   'cls'=>'bg-pink-100 text-pink-700'],
        'suite_a'   => ['label'=>'جناح A',  'cls'=>'bg-violet-100 text-violet-700'],
        'suite_b'   => ['label'=>'جناح B',  'cls'=>'bg-blue-100 text-blue-700'],
        'hall'      => ['label'=>'صالة',    'cls'=>'bg-slate-100 text-slate-600'],
        'apartment' => ['label'=>'شقة',     'cls'=>'bg-amber-100 text-amber-700'],
    ];
@endphp

<div x-data="roomsPage()" x-init="init()">

{{-- ════════ Stats Row ════════ --}}
<div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
    <a href="{{ route('rooms.index', array_merge(request()->except('status'), [])) }}"
       class="stat-chip bg-white" style="border-color:{{ request('status') ? '#f0f2f5' : '#0F4C75' }};">
        <span class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#e8f0f7;">
            <svg class="w-5 h-5" fill="none" stroke="#0F4C75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        </span>
        <div>
            <div class="text-xl font-bold text-gray-800 leading-none">{{ $total }}</div>
            <div class="text-xs text-gray-500 mt-0.5">إجمالي</div>
        </div>
    </a>

    @foreach([
        ['status'=>'available',      'count'=>$available,   'label'=>'متاحة',      'color'=>'#10b981','bg'=>'#ecfdf5','icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['status'=>'occupied',       'count'=>$occupied,    'label'=>'مشغولة',     'color'=>'#ef4444','bg'=>'#fef2f2','icon'=>'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
        ['status'=>'under_inspection','count'=>$inspection, 'label'=>'تحت الفحص', 'color'=>'#f59e0b','bg'=>'#fffbeb','icon'=>'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
        ['status'=>'maintenance',    'count'=>$maintenance, 'label'=>'صيانة',      'color'=>'#6b7280','bg'=>'#f9fafb','icon'=>'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z'],
    ] as $s)
    <a href="{{ route('rooms.index', array_merge(request()->except('status'), ['status'=>$s['status']])) }}"
       class="stat-chip bg-white"
       style="border-color:{{ request('status')===$s['status'] ? $s['color'] : '#f0f2f5' }};">
        <span class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:{{ $s['bg'] }};">
            <svg class="w-5 h-5" fill="none" stroke="{{ $s['color'] }}" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $s['icon'] }}"/></svg>
        </span>
        <div>
            <div class="text-xl font-bold leading-none" style="color:{{ $s['color'] }};">{{ $s['count'] }}</div>
            <div class="text-xs text-gray-500 mt-0.5">{{ $s['label'] }}</div>
        </div>
    </a>
    @endforeach
</div>

{{-- ════════ Filter + Actions Bar ════════ --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" id="filterForm">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">بحث عن غرفة</label>
                <div class="relative">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text" x-model="search" placeholder="اكتب رقم الغرفة..." autocomplete="off"
                           @keydown.enter.prevent
                           class="w-full border border-gray-200 rounded-xl pr-9 pl-3 py-2.5 text-sm bg-gray-50 focus:bg-white focus:border-blue-400 outline-none transition">
                </div>
            </div>
            <div class="flex-1 min-w-40">
                <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">الفئة</label>
                <select name="type" onchange="this.form.submit()"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm bg-gray-50 focus:bg-white focus:border-blue-400 outline-none transition">
                    <option value="">جميع الأنواع</option>
                    @foreach($roomTypes as $type)
                    <option value="{{ $type->name }}" {{ request('type')==$type->name?'selected':'' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-40">
                <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">التصنيف</label>
                <select name="sub_type" onchange="this.form.submit()"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm bg-gray-50 focus:bg-white focus:border-blue-400 outline-none transition">
                    <option value="">جميع التصنيفات</option>
                    <option value="regular"   {{ request('sub_type')==='regular'   ?'selected':'' }}>عادية</option>
                    <option value="double"    {{ request('sub_type')==='double'    ?'selected':'' }}>زوجية</option>
                    <option value="suite_a"   {{ request('sub_type')==='suite_a'   ?'selected':'' }}>جناح A</option>
                    <option value="suite_b"   {{ request('sub_type')==='suite_b'   ?'selected':'' }}>جناح B</option>
                    <option value="hall"      {{ request('sub_type')==='hall'      ?'selected':'' }}>صالة</option>
                    <option value="apartment" {{ request('sub_type')==='apartment' ?'selected':'' }}>شقة</option>
                </select>
            </div>
            <div class="min-w-32">
                <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">الطابق</label>
                <select name="floor" onchange="this.form.submit()"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm bg-gray-50 focus:bg-white focus:border-blue-400 outline-none transition">
                    <option value="">الكل</option>
                    @foreach($floors as $floor)
                    <option value="{{ $floor }}" {{ request('floor')==$floor?'selected':'' }}>طابق {{ $floor }}</option>
                    @endforeach
                </select>
            </div>
            <input type="hidden" name="status" value="{{ request('status') }}">

            @if(request()->hasAny(['type','sub_type','floor','status']))
            <a href="{{ route('rooms.index') }}"
               class="flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm text-gray-500 bg-gray-50 hover:bg-gray-100 transition border border-gray-200">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                مسح
            </a>
            @endif

            <div class="flex items-center gap-2 mr-auto">
                @can('rooms.edit')
                <button type="button" @click="bulkPriceModal=true"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium transition border-2"
                        style="border-color:#0F4C75; color:#0F4C75;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a2 2 0 014-4z"/></svg>
                    توحيد سعر الغرف
                </button>
                @endcan
                @can('rooms.create')
                <a href="{{ route('rooms.create') }}"
                   class="flex items-center gap-2 px-4 py-2.5 text-white rounded-xl text-sm font-medium transition"
                   style="background:#0F4C75;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    إضافة غرفة
                </a>
                @endcan
            </div>
        </div>
    </form>
</div>

{{-- ════════ Bulk Select Toolbar ════════ --}}
<div x-show="selectMode" x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 -translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     class="rounded-2xl p-3.5 mb-4 flex items-center gap-3 flex-wrap"
     style="background:#eff6ff; border:1.5px solid #bfdbfe;">
    <button @click="toggleAll()"
            class="flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-xl transition"
            :class="allSelected ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-blue-700 border border-blue-300 hover:bg-blue-50'">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span x-text="allSelected ? 'إلغاء الكل' : 'تحديد الكل'"></span>
    </button>

    <div class="h-5 w-px bg-blue-200 hidden sm:block"></div>

    <div class="flex gap-2 flex-wrap">
        @foreach(['regular'=>'عادية','double'=>'زوجية','suite_a'=>'جناح A','suite_b'=>'جناح B','hall'=>'صالة','apartment'=>'شقة'] as $val=>$label)
        <button @click="selectBySubType('{{ $val }}')"
                class="filter-chip bg-white border-blue-200 text-blue-700 hover:bg-blue-600 hover:text-white hover:border-blue-600">
            {{ $label }}
        </button>
        @endforeach
    </div>

    <div class="flex items-center gap-3 mr-auto">
        <span class="text-sm font-bold text-blue-800 bg-blue-100 px-3 py-1 rounded-lg">
            <span x-text="selectedIds.length"></span> محددة
        </span>
        <button x-show="selectedIds.length > 0" @click="openBulkPrice()"
                class="flex items-center gap-2 px-4 py-2 text-white text-sm font-semibold rounded-xl shadow-sm transition hover:opacity-90"
                style="background:#0F4C75;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            تحديث السعر
        </button>
        <button @click="selectMode=false; selectedIds=[]"
                class="text-sm text-blue-500 hover:text-blue-700 font-medium px-2 py-2 rounded-lg hover:bg-blue-50 transition">
            إلغاء
        </button>
    </div>
</div>

{{-- ════════ Grid Header ════════ --}}
<div class="flex items-center justify-between mb-3">
    <p class="text-sm text-gray-500 font-medium">
        عرض <span class="font-bold text-gray-700">{{ $total }}</span> غرفة
        @if(request()->hasAny(['type','sub_type','floor','status']))
        <span class="text-xs text-blue-500 mr-1">· فلترة مفعّلة</span>
        @endif
    </p>
    @can('rooms.edit')
    <button @click="selectMode=!selectMode; if(!selectMode) selectedIds=[]"
            class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition"
            :class="selectMode
                ? 'bg-blue-600 text-white shadow-sm'
                : 'bg-white border border-gray-200 text-gray-600 hover:border-blue-400 hover:text-blue-600'">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
        <span x-text="selectMode ? 'إلغاء التحديد' : 'تحديد متعدد'"></span>
    </button>
    @endcan
</div>

{{-- ════════ Rooms Grid ════════ --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
    @forelse($rooms as $room)
    @php
        $statusMeta = [
            'available'       => ['bar'=>'status-bar-available',   'badge'=>'bg-emerald-100 text-emerald-700', 'label'=>'متاحة'],
            'occupied'        => ['bar'=>'status-bar-occupied',    'badge'=>'bg-red-100 text-red-700',         'label'=>'مشغولة'],
            'under_inspection'=> ['bar'=>'status-bar-inspection',  'badge'=>'bg-amber-100 text-amber-700',     'label'=>'تحت الفحص'],
            'maintenance'     => ['bar'=>'status-bar-maintenance', 'badge'=>'bg-gray-100 text-gray-600',       'label'=>'صيانة'],
        ];
        $meta = $statusMeta[$room->status] ?? $statusMeta['maintenance'];
    @endphp

    <div class="room-card"
         x-show="!search || @js((string) $room->room_number).toLowerCase().includes(search.trim().toLowerCase())"
         x-cloak
         :class="selectedIds.includes({{ $room->id }}) ? 'selected' : ''">

        {{-- Status bar --}}
        <span class="status-bar {{ $meta['bar'] }}"></span>

        {{-- Checkbox (select mode) --}}
        <div x-show="selectMode"
             class="absolute top-3 right-3 z-10"
             @click.stop="toggleSelect({{ $room->id }})">
            <div class="w-5 h-5 rounded-md border-2 flex items-center justify-center cursor-pointer shadow-sm transition-all"
                 :class="selectedIds.includes({{ $room->id }})
                     ? 'bg-blue-600 border-blue-600'
                     : 'bg-white border-gray-300 hover:border-blue-400'">
                <svg x-show="selectedIds.includes({{ $room->id }})"
                     class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
        </div>

        {{-- Card Body --}}
        <div @click="selectMode ? toggleSelect({{ $room->id }}) : openRoom(@js($room->only(['id','room_number','floor','beds_count','status','room_sub_type','price_yer','suite_price_yer'])), @js($room->roomType?->name ?? ''), {{ (float)($room->price_yer ?? 0) }})"
             class="p-4 pb-3 cursor-pointer select-none">

            {{-- Top row: room number + status badge --}}
            <div class="flex items-start justify-between gap-1 mb-2.5">
                <span class="text-2xl font-black text-gray-800 leading-none tracking-tight">
                    {{ $room->room_number }}
                </span>
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-lg whitespace-nowrap {{ $meta['badge'] }}">
                    {{ $meta['label'] }}
                </span>
            </div>

            {{-- Type --}}
            <div class="text-xs font-semibold text-gray-600 mb-1">{{ $room->sub_type_label }}</div>

            {{-- Floor --}}
            <div class="flex items-center gap-1 text-xs text-gray-400 mb-2">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                طابق {{ $room->floor }}
                @if(($room->beds_count ?? 1) > 1)
                <span class="mx-1 text-gray-200">·</span>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12H3a2 2 0 000 4h18a2 2 0 000-4h-2M5 12V8a4 4 0 014-4h6a4 4 0 014 4v4M5 12h14"/></svg>
                {{ $room->beds_count }}
                @endif
            </div>

            {{-- Sub-type badge --}}
            @if($room->room_sub_type && $room->room_sub_type !== 'regular' && isset($subBadges[$room->room_sub_type]))
            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold mb-2 {{ $subBadges[$room->room_sub_type]['cls'] }}">
                {{ $subBadges[$room->room_sub_type]['label'] }}
            </span>
            @endif

            {{-- Price --}}
            @if($room->isSuite() && ($room->suite_price_yer ?? 0) > 0)
            <div class="mt-1 space-y-0.5">
                <div class="flex items-baseline gap-1">
                    <span class="text-sm font-bold text-gray-600">{{ number_format($room->priceFor('YER'), 0) }}</span>
                    <span class="text-[10px] text-gray-400">ر.ي مستقل</span>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-base font-black" style="color:#0F4C75;">{{ number_format($room->suite_price_yer, 0) }}</span>
                    <span class="text-[10px] text-indigo-500 font-medium">ر.ي كامل</span>
                </div>
            </div>
            @else
            <div class="flex items-baseline gap-1 mt-1">
                <span class="text-base font-black" style="color:#0F4C75;">
                    {{ number_format($room->priceFor('YER'), 0) }}
                </span>
                <span class="text-[10px] text-gray-400 font-medium">ر.ي / ليلة</span>
            </div>
            @endif
        </div>

        {{-- Actions --}}
        @canany(['rooms.edit','rooms.delete'])
        <div x-show="!selectMode"
             class="flex border-t border-gray-100 divide-x divide-x-reverse divide-gray-100">
            @can('rooms.edit')
            <a href="{{ route('rooms.edit', $room) }}"
               class="room-action-btn hover:bg-blue-50 font-semibold"
               style="color:#0F4C75;">
                تعديل
            </a>
            @endcan
            @can('rooms.delete')
            <button @click.stop="confirmDelete({{ $room->id }}, '{{ $room->room_number }}')"
                    class="room-action-btn text-red-500 hover:bg-red-50">
                حذف
            </button>
            @endcan
        </div>
        @endcanany
    </div>

    @empty
    <div class="col-span-full py-20 flex flex-col items-center justify-center gap-3 text-gray-400">
        <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center">
            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        </div>
        <p class="font-medium">لا توجد غرف مطابقة</p>
        <a href="{{ route('rooms.index') }}" class="text-sm text-blue-500 hover:underline">إعادة تعيين الفلتر</a>
    </div>
    @endforelse

    {{-- لا نتائج مطابقة لبحث رقم الغرفة (تصفية فورية في المتصفح) --}}
    <div x-show="noMatches" x-cloak class="col-span-full py-16 flex flex-col items-center justify-center gap-2 text-gray-400">
        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <p class="font-medium">لا توجد غرفة برقم «<span x-text="search"></span>»</p>
    </div>
</div>


{{-- ════════ Bulk Price Modal ════════ --}}
@can('rooms.edit')
<div x-show="bulkPriceModal" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(15,30,50,0.6); backdrop-filter:blur(4px);"
     @click.self="bulkPriceModal=false">
    <div x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">

        {{-- Header --}}
        <div class="px-6 py-4 flex items-center justify-between" style="background:#0F4C75;">
            <h3 class="font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a2 2 0 014-4z"/></svg>
                توحيد سعر الغرف
            </h3>
            <button @click="bulkPriceModal=false" class="text-white/70 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('rooms.bulkPrice') }}" class="p-6 space-y-5">
            @csrf

            {{-- إذا كانت هناك غرف محددة يدوياً --}}
            <template x-if="selectedIds.length > 0">
                <div>
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="room_ids[]" :value="id">
                    </template>
                    <div class="flex items-center gap-3 p-3.5 rounded-xl bg-blue-50 border border-blue-100">
                        <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-blue-800">
                                <span x-text="selectedIds.length"></span> غرفة محددة يدوياً
                            </p>
                            <p class="text-xs text-blue-600 mt-0.5" x-show="hasSuiteInSelection">
                                يشمل التحديد أجنحة — ستظهر حقول سعر الجناح
                            </p>
                        </div>
                    </div>
                </div>
            </template>

            {{-- إذا لم تكن هناك غرف محددة — اختر النوع --}}
            <template x-if="selectedIds.length === 0">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">تطبيق على</label>
                    <select name="sub_type" x-model="bulkSubType"
                            class="w-full border-2 border-gray-200 rounded-xl px-4 py-2.5 text-sm bg-gray-50 focus:bg-white focus:border-blue-400 outline-none transition">
                        <option value="">جميع الغرف ({{ $total }})</option>
                        <option value="regular">عادية فقط</option>
                        <option value="double">زوجية فقط</option>
                        <option value="suite">جناح (A+B معاً)</option>
                        <option value="suite_a">جناح A فقط</option>
                        <option value="suite_b">جناح B فقط</option>
                        <option value="hall">صالة فقط</option>
                        <option value="apartment">شقة فقط</option>
                    </select>
                </div>
            </template>

            {{-- ─── حقل السعر العادي (لجميع الأنواع) ─── --}}
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1.5">
                    <span x-show="!hasSuiteInSelection">سعر الليلة (ر.ي)</span>
                    <span x-show="hasSuiteInSelection">سعر القسم المستقل — كل قسم A أو B على حدة (ر.ي)</span>
                </label>
                <div class="relative">
                    <input type="number" name="price_yer" min="0" step="1" placeholder="0"
                           class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-2xl font-black text-center text-gray-800 outline-none focus:border-amber-400 transition bg-gray-50 focus:bg-white">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-gray-400">ر.ي</span>
                </div>
                <p class="text-xs text-gray-400 mt-1 text-center" x-show="!hasSuiteInSelection">اتركه فارغاً لعدم تغييره</p>
                <p class="text-xs text-amber-600 mt-1 text-center" x-show="hasSuiteInSelection">يُستخدم عند تأجير القسم بشكل مستقل</p>
            </div>

            {{-- ─── حقل سعر الجناح الكامل (يظهر فقط عند تضمين الأجنحة) ─── --}}
            <div x-show="hasSuiteInSelection" x-cloak>
                <label class="block text-xs font-bold text-indigo-700 mb-1.5">
                    سعر الجناح كامل — A+B معاً (ر.ي)
                </label>
                <div class="relative">
                    <input type="number" name="suite_price_yer" min="0" step="1" placeholder="0"
                           class="w-full border-2 border-indigo-200 rounded-xl px-4 py-3 text-2xl font-black text-center text-indigo-800 outline-none focus:border-indigo-500 transition bg-indigo-50 focus:bg-white">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-indigo-400">ر.ي</span>
                </div>
                <p class="text-xs text-indigo-500 mt-1 text-center">يُستخدم عند حجز الجناح كامل دفعة واحدة — اتركه فارغاً لعدم تغييره</p>
            </div>

            {{-- رسالة خطأ --}}
            @error('price_yer')
            <p class="text-xs text-red-600 text-center">{{ $message }}</p>
            @enderror

            <button type="submit"
                    class="w-full py-3 text-white font-bold text-sm rounded-xl transition hover:opacity-90 shadow-sm"
                    style="background:#0F4C75;">
                تحديث الأسعار الآن
            </button>
        </form>
    </div>
</div>
@endcan

{{-- ════════ Delete Modal ════════ --}}
@can('rooms.delete')
<div x-show="deleteModal" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(15,30,50,0.6); backdrop-filter:blur(4px);"
     @click.self="deleteModal=false">
    <div x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
        <div class="w-14 h-14 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </div>
        <h3 class="font-bold text-gray-800 text-lg mb-1">حذف الغرفة</h3>
        <p class="text-sm text-gray-500 mb-5">
            هل تريد حذف الغرفة رقم <strong class="text-gray-800" x-text="deleteRoomNumber"></strong> نهائياً؟<br>
            <span class="text-xs text-red-400 mt-1 block">لا يمكن التراجع عن هذا الإجراء</span>
        </p>
        <form :action="`/rooms/${deleteRoomId}`" method="POST" class="flex gap-3">
            @csrf @method('DELETE')
            <button type="submit"
                    class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2.5 rounded-xl text-sm font-bold transition">
                نعم، احذف
            </button>
            <button type="button" @click="deleteModal=false"
                    class="flex-1 border-2 border-gray-200 text-gray-600 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
                إلغاء
            </button>
        </form>
    </div>
</div>
@endcan

{{-- ════════ Room Detail Modal ════════ --}}
<div x-show="modalOpen" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(15,30,50,0.6); backdrop-filter:blur(4px);"
     @click.self="modalOpen=false">
    <div x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">

        {{-- Modal header --}}
        <div class="px-6 py-4 flex items-center justify-between" style="background:#0F4C75;">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                    <span class="text-white font-black text-base" x-text="selectedRoom.room_number"></span>
                </div>
                <div>
                    <h3 class="font-bold text-white text-sm" x-text="'غرفة ' + selectedRoom.room_number"></h3>
                    <p class="text-white/60 text-xs" x-text="selectedRoomType"></p>
                </div>
            </div>
            <button @click="modalOpen=false" class="text-white/70 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="p-5 space-y-4">
            {{-- Info grid --}}
            <div class="grid grid-cols-3 gap-2.5">
                <div class="bg-gray-50 rounded-xl p-3 text-center">
                    <div class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide mb-1">الطابق</div>
                    <div class="font-black text-gray-800 text-lg leading-none" x-text="selectedRoom.floor"></div>
                </div>
                <div class="bg-gray-50 rounded-xl p-3 text-center">
                    <div class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide mb-1">التصنيف</div>
                    <div class="font-bold text-gray-700 text-sm leading-tight" x-text="subTypeLabels[selectedRoom.room_sub_type] || 'عادية'"></div>
                </div>
                <div class="rounded-xl p-3 text-center" style="background:#e8f0f7;">
                    <div class="text-[10px] font-semibold uppercase tracking-wide mb-1" style="color:#0F4C75; opacity:0.7;">السعر/ليلة</div>
                    <div class="font-black text-base leading-none" style="color:#0F4C75;" x-text="selectedRoomPrice.toLocaleString()"></div>
                    <div class="text-[9px] font-bold mt-0.5" style="color:#0F4C75; opacity:0.6;">ر.ي</div>
                </div>
            </div>

            {{-- Status --}}
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                <span class="text-sm font-medium text-gray-600">الحالة الحالية</span>
                <span class="px-3 py-1 rounded-full text-xs font-bold"
                      :class="{
                          'bg-emerald-100 text-emerald-700': selectedRoom.status === 'available',
                          'bg-red-100 text-red-700': selectedRoom.status === 'occupied',
                          'bg-amber-100 text-amber-700': selectedRoom.status === 'under_inspection',
                          'bg-gray-200 text-gray-600': selectedRoom.status === 'maintenance',
                      }"
                      x-text="statusLabels[selectedRoom.status] || selectedRoom.status">
                </span>
            </div>

            {{-- Change status --}}
            @canany(['rooms.edit','rooms.maintenance'])
            <form :action="`/rooms/${selectedRoom.id}/status`" method="POST">
                @csrf
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">تغيير الحالة</label>
                <div class="flex gap-2">
                    <select name="status"
                            class="flex-1 border-2 border-gray-200 rounded-xl px-3 py-2.5 text-sm bg-gray-50 focus:bg-white focus:border-blue-400 outline-none transition">
                        <option value="available">متاحة</option>
                        <option value="under_inspection">تحت الفحص</option>
                        <option value="maintenance">صيانة</option>
                    </select>
                    <button type="submit"
                            class="px-5 py-2.5 text-white text-sm font-bold rounded-xl transition hover:opacity-90 shadow-sm"
                            style="background:#0F4C75;">
                        حفظ
                    </button>
                </div>
                <p class="text-xs text-gray-400 mt-1.5">الحالة "مشغولة" تتغير تلقائياً عبر الحجوزات</p>
            </form>
            @endcanany
        </div>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script>
function roomsPage() {
    return {
        search: '',
        roomNumbers: @json($rooms->pluck('room_number')->map(fn($n) => (string) $n)->values()),
        get noMatches() {
            const q = this.search.trim().toLowerCase();
            if (!q) return false;
            return !this.roomNumbers.some(n => n.toLowerCase().includes(q));
        },
        modalOpen: false,
        deleteModal: false,
        deleteRoomId: null,
        deleteRoomNumber: '',
        selectedRoom: {},
        selectedRoomType: '',
        selectedRoomPrice: 0,
        selectMode: false,
        selectedIds: [],
        bulkPriceModal: false,
        bulkSubType: '',
        allRoomIds: @json($rooms->pluck('id')),
        roomSubTypes: @json($rooms->pluck('room_sub_type', 'id')),
        statusLabels: {
            available: 'متاحة', occupied: 'مشغولة',
            under_inspection: 'تحت الفحص', maintenance: 'صيانة'
        },
        subTypeLabels: {
            regular: 'عادية', double: 'زوجية', suite_a: 'جناح A',
            suite_b: 'جناح B', hall: 'صالة', apartment: 'شقة'
        },
        get allSelected() {
            return this.allRoomIds.length > 0 && this.selectedIds.length === this.allRoomIds.length;
        },
        get hasSuiteInSelection() {
            if (this.selectedIds.length > 0) {
                return this.selectedIds.some(id => ['suite_a','suite_b'].includes(this.roomSubTypes[String(id)]));
            }
            return ['suite','suite_a','suite_b',''].includes(this.bulkSubType);
        },
        init() {},
        openRoom(room, type, price) {
            if (this.selectMode) { this.toggleSelect(room.id); return; }
            this.selectedRoom = room;
            this.selectedRoomType = type;
            this.selectedRoomPrice = price;
            this.modalOpen = true;
        },
        confirmDelete(id, number) {
            this.deleteRoomId = id;
            this.deleteRoomNumber = number;
            this.deleteModal = true;
        },
        toggleSelect(id) {
            const idx = this.selectedIds.indexOf(id);
            if (idx === -1) this.selectedIds.push(id);
            else this.selectedIds.splice(idx, 1);
        },
        toggleAll() {
            this.allSelected ? this.selectedIds = [] : this.selectedIds = [...this.allRoomIds];
        },
        selectBySubType(subType) {
            const ids = Object.entries(this.roomSubTypes)
                .filter(([id, st]) => st === subType || (!st && subType === 'regular'))
                .map(([id]) => parseInt(id));
            const allIn = ids.every(id => this.selectedIds.includes(id));
            if (allIn) this.selectedIds = this.selectedIds.filter(id => !ids.includes(id));
            else ids.forEach(id => { if (!this.selectedIds.includes(id)) this.selectedIds.push(id); });
        },
        openBulkPrice() { this.bulkPriceModal = true; }
    }
}
</script>
@endpush
