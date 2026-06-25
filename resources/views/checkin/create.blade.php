@extends('layouts.app')
@section('title', 'تسجيل الدخول')
@section('page-title', 'تسجيل الدخول')

@push('styles')
<style>
/* ── Design tokens ──────────────────────────────────── */
:root {
    --p:   #0F4C75;
    --p-h: #0d3f61;
    --p-b: #e8f2f9;
    --p-m: #3d7cbb;
    --a:   #D4A574;
}

/* ── Inputs ─────────────────────────────────────────── */
.inp {
    width: 100%;
    padding: .5rem .75rem;
    border: 1.5px solid #dde1e7;
    border-radius: .5rem;
    font-size: .875rem;
    color: #1f2937;
    background: #fff;
    outline: none;
    font-family: inherit;
    transition: border-color .15s, box-shadow .15s;
}
.inp:focus { border-color: var(--p); box-shadow: 0 0 0 3px rgba(15,76,117,.1); }
.inp::placeholder { color: #b8bec8; font-size: .8125rem; }
.lbl { display: block; font-size: .75rem; font-weight: 600; color: #374151; margin-bottom: .3rem; }

/* ── Cards ──────────────────────────────────────────── */
.card {
    background: #fff;
    border-radius: .875rem;
    border: 1px solid #eaedf1;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
    overflow: hidden;
}
.card-hd {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .875rem 1.25rem;
    background: #fafbfc;
    border-bottom: 1px solid #eaedf1;
}
.card-ico {
    width: 2.25rem; height: 2.25rem;
    border-radius: .625rem;
    background: var(--p-b);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

/* ── Section dividers ───────────────────────────────── */
.sec-lbl {
    font-size: .6875rem; font-weight: 700;
    color: #9ca3af; letter-spacing: .07em; text-transform: uppercase;
    display: flex; align-items: center; gap: .5rem;
    margin-bottom: .75rem;
}
.sec-lbl::after { content: ''; flex: 1; height: 1px; background: #f1f3f5; }

/* ── Room tiles ─────────────────────────────────────── */
.rtile {
    border: 1.5px solid #e5e8ed;
    border-radius: .625rem;
    padding: .625rem .5rem;
    cursor: pointer;
    background: #fff;
    transition: all .15s ease;
    text-align: center;
    user-select: none;
}
.rtile:hover {
    border-color: #7fb3d3;
    background: var(--p-b);
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(15,76,117,.1);
}
.rtile-on {
    border-color: var(--p) !important;
    background: var(--p) !important;
    box-shadow: 0 4px 14px rgba(15,76,117,.28) !important;
    transform: translateY(-1px) !important;
}

/* ── Payment option cards ───────────────────────────── */
.pay-opt {
    border: 1.5px solid #e5e8ed;
    border-radius: .625rem;
    padding: .75rem .625rem;
    text-align: center;
    cursor: pointer;
    transition: all .15s;
}
</style>
@endpush

@section('content')
<div x-data="checkInForm()" x-init="init()">
<form id="checkInForm" method="POST" action="{{ route('checkin.store') }}"
      enctype="multipart/form-data" autocomplete="off" @submit="submitting=true">
@csrf
<input type="hidden" name="currency" value="YER">
<input type="hidden" name="room_id" x-model="roomId">
<input type="hidden" name="suite_booking_type" x-model="suiteBookingType">
<input type="hidden" name="total_amount" :value="totalAmount">
<input type="hidden" name="price_per_night" :value="effectiveRoomPrice()">

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

    {{-- ═══════════════════════════════════════════
         LEFT — Rooms + Guest + Companions
    ═══════════════════════════════════════════ --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- ── Room Selection ── --}}
        <div class="card">
            <div class="card-hd">
                <div class="card-ico">
                    <svg style="width:17px;height:17px;color:var(--p)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-gray-800">اختيار الغرفة <span class="text-red-400">*</span></p>
                    <p class="text-xs text-gray-400">الغرف المتاحة — اضغط للاختيار</p>
                </div>
                <div x-show="selectedRoom" x-cloak
                     class="flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-lg text-white shrink-0"
                     style="background:var(--p)">
                    <svg style="width:11px;height:11px" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    <span x-text="roomSelectionLabel()"></span>
                </div>
            </div>

            <div class="p-4">

                {{-- Selected room banner --}}
                <div x-show="selectedRoom" x-cloak
                     class="mb-4 rounded-xl text-white flex items-center justify-between px-4 py-3"
                     style="background:var(--p)">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center font-bold text-sm border border-white/25"
                             style="background:rgba(255,255,255,0.15)"
                             x-text="selectedRoom?.room_number ?? ''"></div>
                        <div>
                            <div class="font-bold text-sm" x-text="roomSelectionLabel()"></div>
                            <div class="text-xs mt-0.5" style="color:rgba(255,255,255,0.6)"
                                 x-text="(selectedRoom?.room_type_name ?? '')"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <div class="text-lg font-bold" x-text="formatNumber(effectiveRoomPrice())"></div>
                            <div class="text-xs" style="color:rgba(255,255,255,0.6)">ر.ي / ليلة</div>
                        </div>
                        <button type="button" @click="clearRoomSelection()"
                                class="text-xs px-3 py-1.5 rounded-lg transition"
                                style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2)"
                                onmouseover="this.style.background='rgba(255,255,255,0.22)'"
                                onmouseout="this.style.background='rgba(255,255,255,0.12)'">تغيير</button>
                    </div>
                </div>

                {{-- Suite alert --}}
                <div x-show="selectedRoom && linkedInfo?.is_always_linked" x-cloak
                     class="mb-4 flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-xl px-4 py-2.5 text-xs text-amber-800">
                    <svg style="width:14px;height:14px;flex-shrink:0;color:#d97706" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    الشقة تُحجز كوحدة كاملة —
                    <strong x-text="(selectedRoom?.room_number ?? '') + ' و ' + (linkedInfo?.linked_number ?? '')"></strong>
                </div>

                {{-- Filters --}}
                <div class="p-3 mb-4 rounded-xl space-y-2" style="background:#f7f8fa">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span style="font-size:10px;font-weight:700;color:#9ca3af;letter-spacing:.07em;text-transform:uppercase">طابق:</span>
                        <button type="button" @click="floorFilter='all'"
                                :class="floorFilter==='all'?'text-white shadow-sm':'bg-white text-gray-500 border border-gray-200 hover:bg-gray-50'"
                                :style="floorFilter==='all'?'background:var(--p)':''"
                                class="px-2.5 py-1 rounded-md text-xs font-semibold transition">الكل</button>
                        @foreach($floors as $floor)
                        <button type="button" @click="floorFilter='{{ $floor }}'"
                                :class="floorFilter==='{{ $floor }}'?'text-white shadow-sm':'bg-white text-gray-500 border border-gray-200 hover:bg-gray-50'"
                                :style="floorFilter==='{{ $floor }}'?'background:var(--p)':''"
                                class="px-2.5 py-1 rounded-md text-xs font-semibold transition">{{ $floor }}</button>
                        @endforeach
                    </div>
                    <div class="h-px" style="background:#ebebeb"></div>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span style="font-size:10px;font-weight:700;color:#9ca3af;letter-spacing:.07em;text-transform:uppercase">نوع:</span>
                        @foreach(['all'=>'الكل','regular'=>'عادية','double'=>'زوجية','suite'=>'جناح','hall'=>'صالة'] as $k=>$v)
                        <button type="button" @click="typeFilter='{{ $k }}'"
                                :class="typeFilter==='{{ $k }}'?'text-white shadow-sm':'bg-white text-gray-500 border border-gray-200 hover:bg-gray-50'"
                                :style="typeFilter==='{{ $k }}'?'background:var(--p)':''"
                                class="px-2.5 py-1 rounded-md text-xs font-semibold transition">{{ $v }}</button>
                        @endforeach
                    </div>
                </div>

                {{-- Room tiles --}}
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2">
                    @foreach($displayRooms as $room)
                    @php
                        $info = $linkedAvailability[$room->id] ?? null;
                        $roomData = [
                            'id'              => $room->id,
                            'room_number'     => $room->room_number,
                            'floor'           => $room->floor,
                            'base_price'      => (float)$room->roomType->base_price,
                            'prices'          => $room->pricesArray(),
                            'suite_price_yer' => (float)($room->suite_price_yer ?? 0),
                            'room_type_name'  => $room->roomType->name,
                            'sub_type'        => $room->room_sub_type,
                        ];
                        $roomTypeKey = match($room->room_sub_type) {
                            'suite_a','suite_b' => 'suite',
                            'hall'              => 'hall',
                            'double'            => 'double',
                            default             => 'regular',
                        };
                    @endphp

                    @if($room->room_sub_type === 'suite_a')
                    @php
                        $doorLabel = rtrim($room->room_number, 'AB');
                        $linkedRoomData = $info ? [
                            'id'              => $info['linked_id'],
                            'room_number'     => $info['linked_number'],
                            'floor'           => $room->floor,
                            'base_price'      => (float)$room->roomType->base_price,
                            'prices'          => $room->pricesArray(),
                            'suite_price_yer' => (float)($room->suite_price_yer ?? 0),
                            'room_type_name'  => $room->roomType->name,
                            'sub_type'        => 'suite_b',
                        ] : null;
                        $suiteActive = "(roomId=='" . $room->id . "'||roomId=='" . ($info ? $info['linked_id'] : '') . "')";
                    @endphp
                    <div x-show="(floorFilter==='all'||floorFilter==='{{ $room->floor }}')&&(typeFilter==='all'||typeFilter==='suite')"
                         class="rounded-xl overflow-hidden border transition-all"
                         :style="{{ $suiteActive }}?'border-color:#4f46e5;box-shadow:0 4px 12px rgba(79,70,229,.2)':'border-color:#c7d2fe'">
                        <div class="px-2.5 pt-2.5 pb-1.5 transition-colors"
                             :style="{{ $suiteActive }}?'background:#4f46e5':'background:#eef2ff'">
                            <div class="flex items-center justify-between">
                                <span class="text-base font-bold transition-colors"
                                      :style="{{ $suiteActive }}?'color:#fff':'color:#312e81'">{{ $doorLabel }}</span>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded transition-colors"
                                      :style="{{ $suiteActive }}?'background:rgba(255,255,255,.2);color:#fff':'background:#c7d2fe;color:#4338ca'">جناح</span>
                            </div>
                            <div class="text-xs mt-0.5 transition-colors"
                                 :style="{{ $suiteActive }}?'color:rgba(255,255,255,.65)':'color:#6b7280'">{{ number_format($room->roomType->base_price,0) }}</div>
                        </div>
                        <div class="flex border-t transition-colors"
                             :style="{{ $suiteActive }}?'border-color:#6366f1':'border-color:#c7d2fe'">
                            <button type="button"
                                    @click="selectRoom({{ json_encode($roomData) }}, {{ json_encode($info) }}, 'a_only')"
                                    class="flex-1 py-1.5 text-xs font-bold transition border-l"
                                    :style="roomId=='{{ $room->id }}'&&suiteBookingType==='a_only'?'background:#4338ca;color:#fff;border-color:#6366f1':'background:#fff;color:#4f46e5;border-color:#c7d2fe'">A</button>
                            @if($info && $info['linked_available'] && $linkedRoomData)
                            <button type="button"
                                    @click="selectRoom({{ json_encode($linkedRoomData) }}, null, 'b_only')"
                                    class="flex-1 py-1.5 text-xs font-bold transition border-l"
                                    :style="roomId=='{{ $info['linked_id'] }}'?'background:#7c3aed;color:#fff;border-color:#6366f1':'background:#fff;color:#7c3aed;border-color:#c7d2fe'">B</button>
                            <button type="button"
                                    @click="selectRoom({{ json_encode($roomData) }}, {{ json_encode($info) }}, 'both')"
                                    class="flex-1 py-1.5 text-xs font-bold transition"
                                    :style="roomId=='{{ $room->id }}'&&suiteBookingType==='both'?'background:#312e81;color:#fff':'background:#fff;color:#4f46e5'">A+B</button>
                            @else
                            <div class="flex-1 py-1.5 text-xs text-center border-l" style="color:#d1d5db;background:#f9fafb;border-color:#e5e7eb">B</div>
                            @endif
                        </div>
                    </div>

                    @else
                    <div x-show="(floorFilter==='all'||floorFilter==='{{ $room->floor }}')&&(typeFilter==='all'||typeFilter==='{{ $roomTypeKey }}')"
                         @click="selectRoom({{ json_encode($roomData) }}, {{ json_encode($info) }})"
                         class="rtile select-none"
                         :class="roomId=='{{ $room->id }}' ? 'rtile-on' : ''">
                        <div class="flex items-start justify-between mb-1.5">
                            <span class="text-base font-bold leading-none"
                                  :style="roomId=='{{ $room->id }}' ? 'color:#fff' : 'color:#111827'">{{ $room->room_number }}</span>
                            @if($room->room_sub_type === 'double')
                            <span class="text-[9px] font-bold px-1 py-0.5 rounded leading-none"
                                  :style="roomId=='{{ $room->id }}' ? 'background:rgba(255,255,255,.2);color:#fff' : 'background:#fce7f3;color:#9d174d'">زوجية</span>
                            @elseif($room->room_sub_type === 'apartment')
                            <span class="text-[9px] font-bold px-1 py-0.5 rounded leading-none"
                                  :style="roomId=='{{ $room->id }}' ? 'background:rgba(255,255,255,.2);color:#fff' : 'background:#fef3c7;color:#92400e'">شقة</span>
                            @elseif($room->room_sub_type === 'hall')
                            <span class="text-[9px] font-bold px-1 py-0.5 rounded leading-none"
                                  :style="roomId=='{{ $room->id }}' ? 'background:rgba(255,255,255,.2);color:#fff' : 'background:#f3f4f6;color:#374151'">صالة</span>
                            @endif
                        </div>
                        <div class="text-[10px] truncate"
                             :style="roomId=='{{ $room->id }}' ? 'color:rgba(255,255,255,.6)' : 'color:#9ca3af'">{{ $room->roomType->name }}</div>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-[10px]"
                                  :style="roomId=='{{ $room->id }}' ? 'color:rgba(255,255,255,.5)' : 'color:#9ca3af'">ط{{ $room->floor }}</span>
                            <span class="text-xs font-bold"
                                  :style="roomId=='{{ $room->id }}' ? 'color:#fff' : 'color:#059669'">{{ number_format($room->roomType->base_price,0) }}</span>
                        </div>
                    </div>
                    @endif
                    @endforeach

                    @if($displayRooms->isEmpty())
                    <div class="col-span-full flex flex-col items-center justify-center py-12 text-gray-400">
                        <svg style="width:48px;height:48px;color:#e5e7eb;margin-bottom:10px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/></svg>
                        <p class="text-sm font-medium">لا توجد غرف متاحة حالياً</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Guest Info ── --}}
        <div class="card">
            <div class="card-hd">
                <div class="card-ico">
                    <svg style="width:17px;height:17px;color:var(--p)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-gray-800">بيانات النزيل الرئيسي</p>
                    <p class="text-xs text-gray-400">معلومات شخصية وهوية</p>
                </div>
            </div>

            <div class="p-5 space-y-5">

                {{-- Personal info --}}
                <div>
                    <p class="sec-lbl">المعلومات الشخصية</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                        <div class="md:col-span-3">
                            <label class="lbl">الاسم الرباعي <span class="text-red-400">*</span></label>
                            <input type="text" name="full_name" x-model="guestData.full_name" required
                                   value="{{ old('full_name') }}" placeholder="أدخل الاسم الرباعي كاملاً..."
                                   class="inp">
                        </div>
                        <div>
                            <label class="lbl">الجنسية</label>
                            <input type="text" name="nationality" x-model="guestData.nationality"
                                   value="{{ old('nationality') }}" placeholder="يمني، سعودي..."
                                   class="inp">
                        </div>
                        <div>
                            <label class="lbl">رقم الجوال <span class="text-red-400">*</span></label>
                            <input type="text" name="phone" x-model="guestData.phone" required
                                   value="{{ old('phone') }}" placeholder="07X XXXX XXXX"
                                   class="inp">
                        </div>
                        <div>
                            <label class="lbl">المهنة</label>
                            <input type="text" name="occupation" x-model="guestData.occupation"
                                   value="{{ old('occupation') }}" placeholder="موظف، تاجر..."
                                   class="inp">
                        </div>
                    </div>
                </div>

                {{-- ID info --}}
                <div>
                    <p class="sec-lbl">بيانات الهوية</p>
                    <div class="rounded-xl p-4 grid grid-cols-1 sm:grid-cols-2 gap-3.5" style="background:#f7f8fa">
                        <div>
                            <label class="lbl">نوع الهوية <span class="text-red-400">*</span></label>
                            <select name="id_type" x-model="guestData.id_type" required class="inp" style="background:#fff">
                                <option value="national_id" {{ old('id_type','national_id')==='national_id'?'selected':'' }}>هوية وطنية</option>
                                <option value="passport"    {{ old('id_type')==='passport'?'selected':'' }}>جواز سفر</option>
                                <option value="residence"   {{ old('id_type')==='residence'?'selected':'' }}>إقامة</option>
                            </select>
                        </div>
                        <div>
                            <label class="lbl">رقم الهوية <span class="text-red-400">*</span></label>
                            <input type="text" name="id_number" x-model="guestData.id_number" required
                                   value="{{ old('id_number') }}" class="inp" style="background:#fff">
                        </div>
                        <div>
                            <label class="lbl">جهة إصدار الهوية</label>
                            <input type="text" name="id_issuer" x-model="guestData.id_issuer"
                                   value="{{ old('id_issuer') }}" class="inp" style="background:#fff">
                        </div>
                        <div>
                            <label class="lbl">تاريخ الإصدار</label>
                            <input type="date" name="id_issue_date" x-model="guestData.id_issue_date"
                                   value="{{ old('id_issue_date') }}" class="inp" style="background:#fff">
                        </div>
                    </div>
                </div>

                {{-- Visit info --}}
                <div>
                    <p class="sec-lbl">معلومات الزيارة</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="lbl">جهة القدوم</label>
                            <input type="text" name="origin" x-model="guestData.origin"
                                   value="{{ old('origin') }}" placeholder="المدينة / المنطقة"
                                   class="inp">
                        </div>
                        <div>
                            <label class="lbl">الغرض من القدوم</label>
                            <input type="text" name="purpose" x-model="guestData.purpose"
                                   value="{{ old('purpose') }}" placeholder="سياحة / عمل / علاج..."
                                   class="inp">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="lbl">ملاحظات</label>
                            <textarea name="notes" x-model="guestData.notes" rows="2"
                                      placeholder="أي ملاحظات خاصة..."
                                      class="inp resize-none">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- ID Image --}}
                <div>
                    <p class="sec-lbl">صورة الهوية <span class="text-red-400" style="font-size:11px;text-transform:none">*</span></p>
                    <div class="relative border-2 border-dashed rounded-xl transition-all cursor-pointer group"
                         :style="idImageName ? 'border-color:var(--p);background:var(--p-b)' : 'border-color:#dde1e7'"
                         @mouseover="!idImageName && (this.style.borderColor='#7fb3d3')"
                         @mouseout="!idImageName && (this.style.borderColor='#dde1e7')"
                         @dragover.prevent @drop.prevent="handleIdImageDrop($event)">
                        <input type="file" name="id_image" accept="image/*,.pdf"
                               class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10"
                               @change="handleIdImageChange($event)">
                        <div class="p-5">
                            <template x-if="!idImageName">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 transition-colors"
                                         style="background:#f3f4f6">
                                        <svg style="width:24px;height:24px;color:#9ca3af" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">اسحب صورة الهوية أو <span style="color:var(--p);text-decoration:underline">اختر ملفاً</span></p>
                                        <p class="text-xs text-gray-400 mt-0.5">JPG, PNG, PDF — بحد أقصى 5MB</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="idImageName">
                                <div class="flex items-center gap-3">
                                    <img x-show="idImagePreview" :src="idImagePreview"
                                         class="w-20 h-14 object-cover rounded-xl border shrink-0"
                                         style="border-color:var(--p-m)">
                                    <div class="w-20 h-14 rounded-xl flex items-center justify-center shrink-0"
                                         x-show="!idImagePreview" style="background:#f3f4f6;border:1px solid #e5e7eb">
                                        <svg style="width:22px;height:22px;color:#9ca3af" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-800 truncate" x-text="idImageName"></p>
                                        <p class="text-xs mt-1 flex items-center gap-1" style="color:#059669">
                                            <svg style="width:13px;height:13px" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                            تم الاختيار
                                        </p>
                                        <button type="button" @click.stop="idImagePreview=null; idImageName=''"
                                                class="text-xs text-red-400 hover:text-red-600 relative z-20">تغيير</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ── Companions ── --}}
        <div class="card">
            <div class="card-hd">
                <div class="card-ico" style="background:#f5f3ff">
                    <svg style="width:17px;height:17px;color:#7c3aed" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-gray-800">
                        المرافقون
                        <span x-show="companions.length > 0"
                              class="mr-1.5 text-xs font-bold px-2 py-0.5 rounded-full"
                              style="background:#ede9fe;color:#7c3aed"
                              x-text="companions.length"></span>
                    </p>
                    <p class="text-xs text-gray-400">اختياري — أفراد العائلة المرافقون</p>
                </div>
                <button type="button" @click="addCompanion()"
                        class="flex items-center gap-1.5 text-xs font-semibold px-3.5 py-2 rounded-lg text-white transition shadow-sm"
                        style="background:#7c3aed"
                        onmouseover="this.style.background='#6d28d9'"
                        onmouseout="this.style.background='#7c3aed'">
                    <svg style="width:13px;height:13px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    إضافة مرافق
                </button>
            </div>

            <div x-show="companions.length === 0" class="flex flex-col items-center py-10 text-gray-400">
                <svg style="width:44px;height:44px;color:#e5e7eb;margin-bottom:8px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <p class="text-sm font-medium">لا يوجد مرافقون</p>
                <p class="text-xs mt-1" style="color:#d1d5db">اضغط "إضافة مرافق" للإضافة</p>
            </div>

            <div x-show="companions.length > 0" class="p-4 space-y-3">
                <template x-for="(comp, idx) in companions" :key="idx">
                    <div class="rounded-xl overflow-hidden border" style="border-color:#ede9fe">
                        <div class="flex items-center justify-between px-4 py-2.5 text-white" style="background:#7c3aed">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full text-xs font-bold flex items-center justify-center"
                                      style="background:rgba(255,255,255,.2)" x-text="idx+1"></span>
                                <span class="text-sm font-semibold" x-text="comp.full_name || 'مرافق جديد'"></span>
                            </div>
                            <button type="button" @click="removeCompanion(idx)"
                                    class="text-xs flex items-center gap-1 px-2 py-1 rounded-md transition"
                                    style="color:rgba(255,255,255,.7)"
                                    onmouseover="this.style.background='rgba(255,255,255,.15)'"
                                    onmouseout="this.style.background='transparent'">
                                <svg style="width:13px;height:13px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                حذف
                            </button>
                        </div>
                        <div class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-3" style="background:#faf9ff">
                            <div class="sm:col-span-2">
                                <label class="lbl">الاسم الكامل <span class="text-red-400">*</span></label>
                                <input type="text" :name="`companions[${idx}][full_name]`" x-model="comp.full_name" required class="inp">
                            </div>
                            <div>
                                <label class="lbl">صلة القرابة <span class="text-red-400">*</span></label>
                                <select :name="`companions[${idx}][relationship]`" x-model="comp.relationship" required class="inp" style="background:#fff">
                                    <option value="wife">زوجة</option>
                                    <option value="son">ابن</option>
                                    <option value="daughter">ابنة</option>
                                    <option value="brother">أخ</option>
                                    <option value="sister">أخت</option>
                                    <option value="father">أب</option>
                                    <option value="mother">أم</option>
                                    <option value="other">أخرى</option>
                                </select>
                            </div>
                            <div>
                                <label class="lbl">الجنسية <span class="text-red-400">*</span></label>
                                <input type="text" :name="`companions[${idx}][nationality]`" x-model="comp.nationality" required class="inp">
                            </div>
                            <div>
                                <label class="lbl">نوع الهوية</label>
                                <select :name="`companions[${idx}][id_type]`" x-model="comp.id_type" class="inp" style="background:#fff">
                                    <option value="national_id">هوية وطنية</option>
                                    <option value="passport">جواز سفر</option>
                                    <option value="residence">إقامة</option>
                                </select>
                            </div>
                            <div>
                                <label class="lbl">رقم الهوية</label>
                                <input type="text" :name="`companions[${idx}][id_number]`" x-model="comp.id_number" class="inp">
                            </div>
                            <div>
                                <label class="lbl">جهة الإصدار</label>
                                <input type="text" :name="`companions[${idx}][id_issuer]`" x-model="comp.id_issuer" class="inp">
                            </div>
                            <div>
                                <label class="lbl">تاريخ الإصدار</label>
                                <input type="date" :name="`companions[${idx}][id_issue_date]`" x-model="comp.id_issue_date" class="inp">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="lbl">صورة الهوية <span class="text-red-400">*</span></label>
                                <label class="flex items-center gap-3 cursor-pointer rounded-xl p-3 transition group border-2 border-dashed"
                                       style="border-color:#c4b5fd"
                                       onmouseover="this.style.background='#f5f3ff'"
                                       onmouseout="this.style.background=''">
                                    <input type="file" :name="`companions[${idx}][id_image]`" accept="image/*,.pdf" required
                                           class="hidden" @change="handleCompanionIdImage($event, idx)">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 transition-colors"
                                         style="background:#ede9fe">
                                        <svg style="width:14px;height:14px;color:#7c3aed" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-gray-600">اختر صورة الهوية</p>
                                        <p class="text-xs text-gray-400">JPG, PNG, PDF</p>
                                    </div>
                                    <img x-show="comp.id_preview && comp.id_preview !== '__pdf__'"
                                         :src="comp.id_preview"
                                         class="h-10 w-14 rounded-lg object-cover shrink-0"
                                         style="border:1px solid #c4b5fd">
                                    <span x-show="comp.id_preview === '__pdf__'"
                                          class="text-xs px-2 py-1 rounded-md shrink-0 font-medium"
                                          style="background:#ede9fe;color:#7c3aed;border:1px solid #c4b5fd">PDF ✓</span>
                                </label>
                            </div>
                            <div x-show="comp.relationship === 'wife'" class="sm:col-span-3">
                                <label class="lbl text-red-600">وثيقة الزواج <span class="text-red-400">*</span></label>
                                <label class="flex items-center gap-3 cursor-pointer rounded-xl p-3 border-2 border-dashed transition"
                                       style="border-color:#fca5a5"
                                       onmouseover="this.style.background='#fef2f2'"
                                       onmouseout="this.style.background=''">
                                    <input type="file" :name="`companions[${idx}][marriage_doc]`" accept="image/*,.pdf"
                                           :required="comp.relationship === 'wife'" class="hidden">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style="background:#fee2e2">
                                        <svg style="width:14px;height:14px;color:#ef4444" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-red-600">رفع وثيقة الزواج — مطلوب</p>
                                        <p class="text-xs text-gray-400">JPG, PNG, PDF</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </div>{{-- /left column --}}

    {{-- ═══════════════════════════════════════════
         RIGHT — Booking Panel (sticky)
    ═══════════════════════════════════════════ --}}
    <div class="lg:sticky lg:top-5 lg:self-start space-y-4">

        <div class="card">

            {{-- ── Dates ── --}}
            <div class="card-hd">
                <div class="card-ico">
                    <svg style="width:17px;height:17px;color:var(--p)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <p class="text-sm font-bold text-gray-800">مدة الإقامة</p>
            </div>
            <div class="p-4 space-y-3">

                {{-- Check-in date (readonly) --}}
                <div>
                    <label class="lbl">تاريخ الوصول</label>
                    <div class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm"
                         style="background:#f7f8fa;border:1.5px solid #e5e7eb">
                        <svg style="width:15px;height:15px;color:#9ca3af;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span x-text="checkInDate" class="font-semibold text-gray-800 flex-1"></span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded"
                              style="background:var(--p-b);color:var(--p)">اليوم</span>
                    </div>
                    <input type="hidden" name="check_in_date" :value="checkInDate">
                </div>

                {{-- Nights stepper --}}
                <div>
                    <label class="lbl">عدد الليالي</label>
                    <div class="flex items-center rounded-lg overflow-hidden" style="border:1.5px solid #dde1e7">
                        <button type="button"
                                @click="if(nightsInput>1){nightsInput--;updateCheckoutFromNights()}"
                                class="w-10 h-10 flex items-center justify-center text-lg font-light transition shrink-0"
                                style="background:#f7f8fa;color:#6b7280;border-left:1.5px solid #dde1e7"
                                onmouseover="this.style.background='#eaedf1'" onmouseout="this.style.background='#f7f8fa'">−</button>
                        <input type="number" x-model.number="nightsInput" min="1" max="365"
                               @input="updateCheckoutFromNights()"
                               class="flex-1 h-10 text-center text-sm font-bold text-gray-800 outline-none border-none bg-white">
                        <button type="button"
                                @click="nightsInput++;updateCheckoutFromNights()"
                                class="w-10 h-10 flex items-center justify-center text-lg font-light transition shrink-0"
                                style="background:#f7f8fa;color:#6b7280;border-right:1.5px solid #dde1e7"
                                onmouseover="this.style.background='#eaedf1'" onmouseout="this.style.background='#f7f8fa'">+</button>
                    </div>
                </div>

                {{-- Check-out --}}
                <div>
                    <label class="lbl">تاريخ المغادرة <span class="text-red-400">*</span></label>
                    <input type="date" name="check_out_date" x-model="checkOutDate" required
                           :min="checkInDate" @change="calcTotal()" class="inp">
                </div>

                {{-- Check-in time --}}
                <div>
                    <label class="lbl">وقت الوصول</label>
                    <input type="time" name="check_in_time" x-model="checkInTime" class="inp">
                </div>
            </div>

            {{-- ── Total ── --}}
            <div x-show="nights > 0 && selectedRoom" style="border-top:1px solid #eaedf1">
                <div class="p-4" style="background:#f7f8fa">
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-2">
                        <span x-text="nights + ' ليلة × ' + formatNumber(effectiveRoomPrice()) + ' ر.ي'"></span>
                        <button type="button" x-show="!showPriceOverride"
                                @click="showPriceOverride=true"
                                class="text-xs underline font-medium" style="color:var(--a)">تعديل السعر</button>
                        <button type="button" x-show="showPriceOverride"
                                @click="showPriceOverride=false;customPrice=null;calcTotal()"
                                class="text-xs font-medium text-red-400 hover:text-red-600">إلغاء التعديل</button>
                    </div>
                    <div x-show="showPriceOverride" class="mb-3">
                        <input type="number" min="3000" max="100000" step="100"
                               :placeholder="'الأصلي: '+formatNumber(roomBasePriceFor('YER'))"
                               x-model.number="customPrice" @input="calcTotal()"
                               class="inp" style="background:#fff;font-size:.8125rem;padding:.4rem .75rem">
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-600">الإجمالي</span>
                        <span class="text-2xl font-bold" style="color:var(--p)"
                              x-text="formatNumber(totalAmount) + ' ر.ي'"></span>
                    </div>
                </div>
            </div>

            {{-- ── Payment ── --}}
            <div style="border-top:1px solid #eaedf1">
                <div class="card-hd" style="border-bottom:1px solid #eaedf1">
                    <div class="card-ico" style="background:#f0fdf4">
                        <svg style="width:17px;height:17px;color:#059669" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <p class="text-sm font-bold text-gray-800">الدفع</p>
                </div>
                <div class="p-4 space-y-4">

                    {{-- Payment status --}}
                    <div>
                        <label class="lbl mb-2">حالة الدفع <span class="text-red-400">*</span></label>
                        <div class="grid grid-cols-3 gap-2">

                            {{-- Paid --}}
                            <label class="cursor-pointer" @click="paymentStatus='paid'">
                                <input type="radio" name="payment_status" value="paid" x-model="paymentStatus" class="sr-only">
                                <div class="pay-opt"
                                     :style="paymentStatus==='paid'?'border-color:#10b981;background:#ecfdf5':'border-color:#e5e8ed;background:#fff'">
                                    <svg style="width:18px;height:18px;margin:0 auto 4px;display:block;transition:color .15s"
                                         :style="paymentStatus==='paid'?'color:#10b981':'color:#d1d5db'"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-xs font-semibold transition-colors"
                                       :style="paymentStatus==='paid'?'color:#065f46':'color:#6b7280'">مدفوع</p>
                                </div>
                            </label>

                            {{-- Partial --}}
                            <label class="cursor-pointer" @click="paymentStatus='partial'">
                                <input type="radio" name="payment_status" value="partial" x-model="paymentStatus" class="sr-only">
                                <div class="pay-opt"
                                     :style="paymentStatus==='partial'?'border-color:#3b82f6;background:#eff6ff':'border-color:#e5e8ed;background:#fff'">
                                    <svg style="width:18px;height:18px;margin:0 auto 4px;display:block;transition:color .15s"
                                         :style="paymentStatus==='partial'?'color:#3b82f6':'color:#d1d5db'"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-xs font-semibold transition-colors"
                                       :style="paymentStatus==='partial'?'color:#1e40af':'color:#6b7280'">جزئي</p>
                                </div>
                            </label>

                            {{-- Deferred --}}
                            <label class="cursor-pointer" @click="paymentStatus='deferred'">
                                <input type="radio" name="payment_status" value="deferred" x-model="paymentStatus" class="sr-only">
                                <div class="pay-opt"
                                     :style="paymentStatus==='deferred'?'border-color:#f59e0b;background:#fffbeb':'border-color:#e5e8ed;background:#fff'">
                                    <svg style="width:18px;height:18px;margin:0 auto 4px;display:block;transition:color .15s"
                                         :style="paymentStatus==='deferred'?'color:#f59e0b':'color:#d1d5db'"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-xs font-semibold transition-colors"
                                       :style="paymentStatus==='deferred'?'color:#92400e':'color:#6b7280'">آجل</p>
                                </div>
                            </label>

                        </div>
                    </div>

                    {{-- Partial amount --}}
                    <div x-show="paymentStatus === 'partial'">
                        <label class="lbl">المبلغ المدفوع (ر.ي) <span class="text-red-400">*</span></label>
                        <input type="number" name="paid_amount" x-model="paidAmount" step="0.01" min="0"
                               :max="totalAmount" placeholder="0.00" class="inp">
                    </div>
                    <template x-if="paymentStatus === 'paid'"><input type="hidden" name="paid_amount" :value="totalAmount"></template>
                    <template x-if="paymentStatus === 'deferred'"><input type="hidden" name="paid_amount" value="0"></template>

                    {{-- Payment method --}}
                    <div>
                        <label class="lbl mb-1.5">طريقة الدفع</label>
                        <div class="space-y-1.5">
                            @foreach(['cash'=>'نقدي','pos'=>'POS','bank_transfer'=>'تحويل بنكي'] as $val=>$lbl)
                            <label class="flex items-center gap-2.5 cursor-pointer p-2.5 rounded-lg transition"
                                   :style="paymentStatus==='deferred' && '{{ $val }}' !== 'cash' ? 'opacity:.4;pointer-events:none' : ''"
                                   onmouseover="this.style.background='#f7f8fa'" onmouseout="this.style.background=''">
                                <input type="radio" name="payment_method" value="{{ $val }}" x-model="paymentMethod"
                                       :disabled="paymentStatus==='deferred' && '{{ $val }}' !== 'cash'"
                                       class="w-4 h-4" style="accent-color:var(--p)">
                                <span class="text-sm text-gray-700 font-medium">{{ $lbl }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Currency note --}}
                    <div>
                        <label class="lbl">ملاحظة العملة (اختياري)</label>
                        <input type="text" name="payment_notes"
                               placeholder="مثال: دفع 100 ر.س بسعر صرف 400"
                               class="inp">
                    </div>

                    {{-- Bank transfer --}}
                    <div x-show="paymentMethod === 'bank_transfer'" x-cloak
                         class="rounded-xl p-3 space-y-3"
                         style="background:#eff6ff;border:1px solid #bfdbfe">
                        <p class="text-xs font-semibold" style="color:#1d4ed8">يجب تقديم سند أو رقم مرجع</p>
                        <div>
                            <label class="lbl">رقم المرجع</label>
                            <input type="text" name="bank_transfer_ref" placeholder="TRF-001..."
                                   class="inp" style="background:#fff">
                        </div>
                        <div x-data="{ receiptName: '' }">
                            <label class="lbl">سند التحويل</label>
                            <label class="flex items-center gap-2.5 cursor-pointer rounded-xl p-3 transition border-2 border-dashed"
                                   style="border-color:#93c5fd"
                                   onmouseover="this.style.background='#dbeafe50'" onmouseout="this.style.background=''">
                                <input type="file" name="bank_receipt" accept="image/*,.pdf" class="hidden"
                                       @change="receiptName = $event.target.files[0]?.name ?? ''">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style="background:#2563eb">
                                    <svg style="width:14px;height:14px;color:#fff" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-blue-800 truncate" x-text="receiptName || 'اختر ملف'"></p>
                                    <p class="text-xs font-medium" style="color:#059669" x-show="receiptName">تم الاختيار ✓</p>
                                </div>
                            </label>
                        </div>
                    </div>

                </div>
            </div>

        </div>{{-- /combined card --}}

        {{-- Submit --}}
        <button type="submit" :disabled="submitting"
                class="w-full flex items-center justify-center gap-2.5 text-sm font-bold text-white rounded-xl transition"
                style="padding:.9rem 1rem;background:var(--p);box-shadow:0 4px 14px rgba(15,76,117,.28)"
                :style="submitting?'opacity:.6;cursor:not-allowed':''"
                onmouseover="if(!this.disabled)this.style.background='var(--p-h)'"
                onmouseout="this.style.background='var(--p)'">
            <template x-if="!submitting">
                <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
            </template>
            <template x-if="submitting">
                <svg style="width:16px;height:16px" class="animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            </template>
            <span x-text="submitting ? 'جارٍ تسجيل الدخول...' : 'تأكيد تسجيل الدخول'"></span>
        </button>

        <p class="text-center text-xs text-gray-400 lg:hidden"
           x-show="totalAmount > 0"
           x-text="nights + ' ليلة × ' + formatNumber(effectiveRoomPrice()) + ' = ' + formatNumber(totalAmount) + ' ر.ي'"></p>

    </div>{{-- /right column --}}

</div>{{-- /grid --}}
</form>
</div>
@endsection

@push('scripts')
@if(session('success'))
<script>sessionStorage.removeItem('hotel_checkin_form');</script>
@endif
<script>
const CHECKIN_SESSION_KEY = 'hotel_checkin_form';
const HAS_BACKEND_ERRORS  = {{ $errors->any() ? 'true' : 'false' }};

function checkInForm() {
    return {
        guestData: {
            full_name:    '{{ old("full_name", "") }}',
            nationality:  '{{ old("nationality", "") }}',
            occupation:   '{{ old("occupation", "") }}',
            origin:       '{{ old("origin", "") }}',
            purpose:      '{{ old("purpose", "") }}',
            id_type:      '{{ old("id_type", "national_id") }}',
            id_number:    '{{ old("id_number", "") }}',
            id_issuer:    '{{ old("id_issuer", "") }}',
            id_issue_date:'{{ old("id_issue_date", "") }}',
            phone:        '{{ old("phone", "") }}',
            notes:        '{{ old("notes", "") }}',
        },
        companions: [],
        selectedRoom: null,
        linkedInfo: null,
        roomId: '',
        suiteBookingType: 'a_only',
        checkInDate: '',
        checkInTime: '',
        checkOutDate: '',
        nights: 0,
        nightsInput: 1,
        totalAmount: 0,
        customPrice: null,
        paymentStatus: '{{ old("payment_status", "paid") }}',
        paymentMethod: '{{ old("payment_method", "cash") }}',
        paidAmount: 0,
        showPriceOverride: false,
        idImagePreview: null,
        idImageName: '',
        floorFilter: 'all',
        typeFilter: 'all',
        submitting: false,

        init() {
            const today = new Date().toISOString().split('T')[0];
            this.checkInDate = today;
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            this.checkOutDate = tomorrow.toISOString().split('T')[0];
            const now = new Date();
            this.checkInTime = now.toTimeString().slice(0, 5);
            if (HAS_BACKEND_ERRORS) {
                this.restoreFromSession();
            } else {
                sessionStorage.removeItem(CHECKIN_SESSION_KEY);
            }
        },

        saveToSession() {
            try {
                sessionStorage.setItem(CHECKIN_SESSION_KEY, JSON.stringify({
                    roomId: this.roomId, selectedRoom: this.selectedRoom,
                    linkedInfo: this.linkedInfo, suiteBookingType: this.suiteBookingType,
                    checkInDate: this.checkInDate, checkInTime: this.checkInTime,
                    checkOutDate: this.checkOutDate, nightsInput: this.nightsInput,
                    paymentStatus: this.paymentStatus, paymentMethod: this.paymentMethod,
                    paidAmount: this.paidAmount, customPrice: this.customPrice,
                    showPriceOverride: this.showPriceOverride,
                    companions: this.companions.map(c => ({ ...c, id_preview: null })),
                }));
            } catch(e) {}
        },

        restoreFromSession() {
            try {
                const raw = sessionStorage.getItem(CHECKIN_SESSION_KEY);
                if (!raw) return;
                const s = JSON.parse(raw);
                this.roomId           = s.roomId           ?? '';
                this.selectedRoom     = s.selectedRoom     ?? null;
                this.linkedInfo       = s.linkedInfo       ?? null;
                this.suiteBookingType = s.suiteBookingType ?? 'a_only';
                this.checkInDate      = s.checkInDate      || this.checkInDate;
                this.checkInTime      = s.checkInTime      || this.checkInTime;
                this.checkOutDate     = s.checkOutDate     || this.checkOutDate;
                this.nightsInput      = s.nightsInput      ?? 1;
                this.paymentStatus    = s.paymentStatus    ?? 'paid';
                this.paymentMethod    = s.paymentMethod    ?? 'cash';
                this.paidAmount       = s.paidAmount       ?? 0;
                this.customPrice      = s.customPrice      ?? null;
                this.showPriceOverride= s.showPriceOverride ?? false;
                this.companions       = s.companions       ?? [];
                this.$nextTick(() => this.calcTotal());
            } catch(e) {}
        },

        addCompanion() {
            this.companions.push({
                full_name: '', nationality: '', id_type: 'national_id',
                id_number: '', id_issuer: '', id_issue_date: '',
                relationship: 'other', id_preview: null,
            });
        },

        removeCompanion(idx) { this.companions.splice(idx, 1); },

        selectRoom(room, info, forcedType = null) {
            this.selectedRoom = room;
            this.linkedInfo   = info || null;
            this.roomId       = room.id;
            if (forcedType) {
                this.suiteBookingType = forcedType;
            } else if (info && !info.is_always_linked) {
                this.suiteBookingType = room.sub_type === 'suite_a' ? 'a_only' : 'b_only';
            } else if (info && info.is_always_linked) {
                this.suiteBookingType = 'both';
            } else {
                this.suiteBookingType = '';
            }
            this.calcTotal();
            this.saveToSession();
        },

        clearRoomSelection() {
            this.selectedRoom = null; this.linkedInfo = null;
            this.roomId = ''; this.suiteBookingType = 'a_only';
            this.totalAmount = 0; this.nights = 0;
            this.saveToSession();
        },

        roomBasePriceFor(cur) {
            if (!this.selectedRoom) return 0;
            const prices = this.selectedRoom.prices || {};
            let val = parseFloat(prices['YER']);
            if (isNaN(val) || val <= 0) val = parseFloat(this.selectedRoom.base_price) || 0;
            return isNaN(val) ? 0 : val;
        },

        effectiveRoomPrice() {
            if (this.customPrice !== null && this.customPrice !== '') return parseFloat(this.customPrice) || 0;
            if (this.suiteBookingType === 'both') {
                const sp = parseFloat(this.selectedRoom?.suite_price_yer) || 0;
                if (sp > 0) return sp;
                return this.roomBasePriceFor('YER') * 2;
            }
            return this.roomBasePriceFor('YER');
        },

        roomSelectionLabel() {
            if (!this.selectedRoom) return '';
            if (this.suiteBookingType === 'both') return 'جناح ' + this.selectedRoom.room_number + ' + ' + (this.linkedInfo?.linked_number ?? '');
            if (this.linkedInfo?.is_always_linked) return 'شقة ' + this.selectedRoom.room_number + ' + ' + (this.linkedInfo?.linked_number ?? '');
            return 'غرفة ' + this.selectedRoom.room_number;
        },

        updateCheckoutFromNights() {
            if (this.checkInDate && this.nightsInput > 0) {
                const d = new Date(this.checkInDate);
                d.setDate(d.getDate() + parseInt(this.nightsInput));
                this.checkOutDate = d.toISOString().split('T')[0];
                this.calcTotal();
            }
        },

        calcTotal() {
            if (this.checkInDate && this.checkOutDate && this.selectedRoom) {
                const d1 = new Date(this.checkInDate), d2 = new Date(this.checkOutDate);
                this.nights      = Math.max(0, Math.floor((d2 - d1) / 86400000));
                this.nightsInput = this.nights || 1;
                this.totalAmount = this.nights * this.effectiveRoomPrice();
                this.saveToSession();
            }
        },

        get effectivePaid() {
            if (this.paymentStatus === 'paid')    return this.totalAmount;
            if (this.paymentStatus === 'partial') return parseFloat(this.paidAmount) || 0;
            return 0;
        },

        formatNumber(n) { return (parseFloat(n) || 0).toLocaleString('ar-YE'); },

        handleIdImageChange(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.idImageName = file.name;
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (ev) => this.idImagePreview = ev.target.result;
                reader.readAsDataURL(file);
            } else { this.idImagePreview = null; }
        },

        handleIdImageDrop(e) {
            const file = e.dataTransfer.files[0];
            if (!file) return;
            const input = document.querySelector('input[name="id_image"]');
            if (input) { const dt = new DataTransfer(); dt.items.add(file); input.files = dt.files; }
            this.handleIdImageChange({ target: { files: [file] } });
        },

        handleCompanionIdImage(e, idx) {
            const file = e.target.files[0];
            if (!file) return;
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (ev) => this.companions[idx].id_preview = ev.target.result;
                reader.readAsDataURL(file);
            } else { this.companions[idx].id_preview = '__pdf__'; }
        },
    }
}
</script>
@endpush
