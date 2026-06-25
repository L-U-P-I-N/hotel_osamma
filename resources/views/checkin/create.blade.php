@extends('layouts.app')
@section('title', 'تسجيل الدخول')
@section('page-title', 'تسجيل الدخول')

@push('styles')
<style>
.field-input {
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 0.625rem 0.875rem;
    font-size: 0.875rem;
    color: #1f2937;
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
    outline: none;
}
.field-input:focus {
    border-color: #0F4C75;
    box-shadow: 0 0 0 3px rgba(15,76,117,.08);
}
.field-input::placeholder { color: #d1d5db; }
.field-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 0.375rem;
}
.section-tag {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #9ca3af;
}
.room-card-base {
    border-radius: 0.625rem;
    padding: 0.625rem;
    cursor: pointer;
    transition: all .15s;
    border: 1.5px solid #e5e7eb;
    background: #fff;
}
.room-card-base:hover { border-color: #9fbedd; box-shadow: 0 2px 8px rgba(15,76,117,.08); transform: translateY(-1px); }
.room-card-selected { border-color: #0F4C75 !important; background: #0F4C75 !important; box-shadow: 0 4px 12px rgba(15,76,117,.25) !important; transform: translateY(-1px) !important; }
.pay-option input:checked + div { border-color: currentColor; background: var(--pay-bg); }
</style>
@endpush

@section('content')

@if($errors->any())
<div class="mb-5 flex items-start gap-3 bg-red-50 border-r-4 border-red-500 rounded-xl p-4">
    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
    <div>
        <p class="font-bold text-red-800 text-sm mb-1">يوجد أخطاء في النموذج:</p>
        <ul class="text-red-700 text-sm space-y-0.5 list-disc list-inside">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<div x-data="checkInForm()" x-init="init()">
<form id="checkInForm" method="POST" action="{{ route('checkin.store') }}"
      enctype="multipart/form-data" autocomplete="off" @submit="submitting = true">
@csrf
<input type="hidden" name="currency" value="YER">
<input type="hidden" name="room_id" x-model="roomId">
<input type="hidden" name="suite_booking_type" x-model="suiteBookingType">
<input type="hidden" name="total_amount" :value="totalAmount">
<input type="hidden" name="price_per_night" :value="effectiveRoomPrice()">

{{-- ─── STICKY STATUS BAR ─── --}}
<div class="sticky top-0 z-40 -mx-4 sm:-mx-6 px-4 sm:px-6 bg-white border-b border-gray-200 flex items-center gap-2 h-12 mb-6">
    <div class="flex items-center gap-1.5 min-w-0">
        <span class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-full px-3 py-1.5 transition-all whitespace-nowrap"
              :class="selectedRoom ? 'bg-primary-800 text-white' : 'bg-gray-100 text-gray-400'">
            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <span x-text="selectedRoom ? roomSelectionLabel() : 'اختر غرفة'"></span>
        </span>
        <span class="hidden sm:inline-flex items-center gap-1.5 text-xs font-semibold rounded-full px-3 py-1.5 transition-all whitespace-nowrap"
              :class="nights > 0 ? 'bg-slate-700 text-white' : 'bg-gray-100 text-gray-400'">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            <span x-text="nights > 0 ? nights + ' ليلة' : 'المدة'"></span>
        </span>
        <span class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-full px-3 py-1.5 transition-all whitespace-nowrap"
              :class="totalAmount > 0 ? 'bg-green-700 text-white' : 'bg-gray-100 text-gray-400'">
            <span x-text="totalAmount > 0 ? formatNumber(totalAmount) + ' ر.ي' : 'الإجمالي'"></span>
        </span>
    </div>
    <div class="flex-1"></div>
    <button type="submit" :disabled="submitting"
            class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition disabled:opacity-50 shadow-sm whitespace-nowrap">
        <template x-if="!submitting"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></template>
        <template x-if="submitting"><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></template>
        <span class="hidden sm:inline" x-text="submitting ? 'جارٍ الحفظ...' : 'تسجيل الدخول'"></span>
    </button>
</div>

{{-- ─── MAIN GRID ─── --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

    {{-- ══════════════════════════════
         LEFT COLUMN — بيانات + غرف
    ══════════════════════════════ --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- ── CARD 1: اختيار الغرفة ── --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 bg-primary-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-primary-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <span class="font-semibold text-gray-800 text-sm">اختيار الغرفة <span class="text-red-400">*</span></span>
                </div>
                <div x-show="selectedRoom"
                     class="inline-flex items-center gap-1.5 bg-primary-800 text-white text-xs font-bold px-3 py-1.5 rounded-lg">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    <span x-text="roomSelectionLabel()"></span>
                </div>
            </div>
            <div class="p-4">

                {{-- Selected room summary strip --}}
                <div x-show="selectedRoom"
                     class="mb-4 flex items-center justify-between bg-primary-800 text-white rounded-xl px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/15 backdrop-blur-sm rounded-lg flex items-center justify-center font-bold text-base border border-white/20"
                             x-text="suiteBookingType === 'both' ? (selectedRoom?.room_number ?? '') + '+' : (selectedRoom?.room_number ?? '')"></div>
                        <div>
                            <div class="font-bold text-sm" x-text="roomSelectionLabel()"></div>
                            <div class="text-xs text-white/60 mt-0.5" x-text="(selectedRoom?.room_type_name ?? '') + ' — الطابق ' + (selectedRoom?.floor ?? '')"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-right">
                            <div class="text-lg font-bold" x-text="formatNumber(effectiveRoomPrice())"></div>
                            <div class="text-xs text-white/60">ر.ي / ليلة</div>
                        </div>
                        <button type="button" @click="clearRoomSelection()"
                                class="text-xs bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-lg transition border border-white/20">تغيير</button>
                    </div>
                </div>

                {{-- Suite alert --}}
                <div x-show="selectedRoom && linkedInfo?.is_always_linked"
                     class="mb-4 flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-xl px-4 py-2.5 text-xs text-amber-800">
                    <svg class="w-4 h-4 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>الشقة تُحجز كوحدة كاملة — سيشمل الحجز الغرفتين
                    <strong x-text="(selectedRoom?.room_number ?? '') + ' و ' + (linkedInfo?.linked_number ?? '')"></strong></span>
                </div>

                {{-- Filters --}}
                <div class="bg-gray-50 rounded-xl p-3 mb-4 space-y-2">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="section-tag w-12 shrink-0">طابق</span>
                        <button type="button" @click="floorFilter='all'"
                                :class="floorFilter==='all'?'bg-gray-800 text-white':'bg-white text-gray-500 border border-gray-200 hover:bg-gray-100'"
                                class="px-2.5 py-1 rounded-md text-xs font-medium transition">الكل</button>
                        @foreach($floors as $floor)
                        <button type="button" @click="floorFilter='{{ $floor }}'"
                                :class="floorFilter==='{{ $floor }}'?'bg-gray-800 text-white':'bg-white text-gray-500 border border-gray-200 hover:bg-gray-100'"
                                class="px-2.5 py-1 rounded-md text-xs font-medium transition">{{ $floor }}</button>
                        @endforeach
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="section-tag w-12 shrink-0">نوع</span>
                        @foreach(['all'=>'الكل','regular'=>'عادية','double'=>'زوجية','suite'=>'جناح','hall'=>'صالة'] as $key=>$label)
                        <button type="button" @click="typeFilter='{{ $key }}'"
                                :class="typeFilter==='{{ $key }}'?'bg-gray-800 text-white':'bg-white text-gray-500 border border-gray-200 hover:bg-gray-100'"
                                class="px-2.5 py-1 rounded-md text-xs font-medium transition">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>

                {{-- Room grid --}}
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2 max-h-64 overflow-y-auto">
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
                    @endphp
                    <div x-show="(floorFilter==='all'||floorFilter==='{{ $room->floor }}')&&(typeFilter==='all'||typeFilter==='suite')"
                         class="rounded-xl overflow-hidden border transition-all"
                         :class="(roomId=='{{ $room->id }}'||roomId=='{{ $info?$info['linked_id']:'' }}')?'border-indigo-600 shadow-md':'border-indigo-200 hover:border-indigo-400 hover:shadow-sm'">
                        <div class="px-2.5 pt-2.5 pb-1.5"
                             :class="(roomId=='{{ $room->id }}'||roomId=='{{ $info?$info['linked_id']:'' }}')?'bg-indigo-600':'bg-indigo-50'">
                            <div class="flex items-center justify-between">
                                <span class="text-base font-bold"
                                      :class="(roomId=='{{ $room->id }}'||roomId=='{{ $info?$info['linked_id']:'' }}')?'text-white':'text-gray-800'">{{ $doorLabel }}</span>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded"
                                      :class="(roomId=='{{ $room->id }}'||roomId=='{{ $info?$info['linked_id']:'' }}')?'bg-white/20 text-white':'bg-indigo-200 text-indigo-800'">جناح</span>
                            </div>
                            <div class="text-xs mt-0.5"
                                 :class="(roomId=='{{ $room->id }}'||roomId=='{{ $info?$info['linked_id']:'' }}')?'text-white/70':'text-gray-500'">{{ number_format($room->roomType->base_price,0) }}</div>
                        </div>
                        <div class="flex border-t"
                             :class="(roomId=='{{ $room->id }}'||roomId=='{{ $info?$info['linked_id']:'' }}')?'border-indigo-500':'border-indigo-200'">
                            <button type="button"
                                    @click="selectRoom({{ json_encode($roomData) }}, {{ json_encode($info) }}, 'a_only')"
                                    :class="roomId=='{{ $room->id }}'&&suiteBookingType==='a_only'?'bg-indigo-700 text-white':'bg-white text-indigo-600 hover:bg-indigo-50'"
                                    class="flex-1 py-1.5 text-xs font-bold transition border-l border-indigo-200">A</button>
                            @if($info && $info['linked_available'] && $linkedRoomData)
                            <button type="button"
                                    @click="selectRoom({{ json_encode($linkedRoomData) }}, null, 'b_only')"
                                    :class="roomId=='{{ $info['linked_id'] }}'?'bg-purple-700 text-white':'bg-white text-purple-600 hover:bg-purple-50'"
                                    class="flex-1 py-1.5 text-xs font-bold transition border-l border-indigo-200">B</button>
                            <button type="button"
                                    @click="selectRoom({{ json_encode($roomData) }}, {{ json_encode($info) }}, 'both')"
                                    :class="roomId=='{{ $room->id }}'&&suiteBookingType==='both'?'bg-indigo-800 text-white':'bg-white text-indigo-600 hover:bg-indigo-50'"
                                    class="flex-1 py-1.5 text-xs font-bold transition">A+B</button>
                            @else
                            <div class="flex-1 py-1.5 text-xs text-center text-gray-300 bg-gray-50 cursor-not-allowed border-l border-indigo-200">B</div>
                            @endif
                        </div>
                    </div>
                    @else
                    <div x-show="(floorFilter==='all'||floorFilter==='{{ $room->floor }}')&&(typeFilter==='all'||typeFilter==='{{ $roomTypeKey }}')"
                         @click="selectRoom({{ json_encode($roomData) }}, {{ json_encode($info) }})"
                         class="room-card-base select-none"
                         :class="roomId=='{{ $room->id }}' ? 'room-card-selected' : ''">
                        <div class="flex items-start justify-between mb-1">
                            <span class="text-[15px] font-bold leading-none"
                                  :class="roomId=='{{ $room->id }}' ? 'text-white' : 'text-gray-800'">{{ $room->room_number }}</span>
                            @if($room->room_sub_type === 'double')
                            <span class="text-[9px] font-bold px-1 py-0.5 rounded leading-none"
                                  :class="roomId=='{{ $room->id }}' ? 'bg-white/20 text-white' : 'bg-pink-100 text-pink-700'">زوجية</span>
                            @elseif($room->room_sub_type === 'apartment')
                            <span class="text-[9px] font-bold px-1 py-0.5 rounded leading-none"
                                  :class="roomId=='{{ $room->id }}' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-700'">شقة</span>
                            @elseif($room->room_sub_type === 'hall')
                            <span class="text-[9px] font-bold px-1 py-0.5 rounded leading-none"
                                  :class="roomId=='{{ $room->id }}' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500'">صالة</span>
                            @endif
                        </div>
                        <div class="text-[11px] truncate mt-0.5"
                             :class="roomId=='{{ $room->id }}' ? 'text-white/60' : 'text-gray-400'">{{ $room->roomType->name }}</div>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-[11px]" :class="roomId=='{{ $room->id }}' ? 'text-white/50' : 'text-gray-400'">ط{{ $room->floor }}</span>
                            <span class="text-xs font-bold" :class="roomId=='{{ $room->id }}' ? 'text-white' : 'text-green-700'">{{ number_format($room->roomType->base_price,0) }}</span>
                        </div>
                    </div>
                    @endif
                    @endforeach

                    @if($displayRooms->isEmpty())
                    <div class="col-span-full text-center py-10 text-gray-400 text-sm">
                        <svg class="w-10 h-10 mx-auto mb-2 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                        لا توجد غرف متاحة حالياً
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── CARD 2: بيانات النزيل ── --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            {{-- Card header --}}
            <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2.5">
                <div class="w-8 h-8 bg-primary-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-primary-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <span class="font-semibold text-gray-800 text-sm">بيانات النزيل الرئيسي</span>
            </div>

            {{-- Personal info --}}
            <div class="p-5">
                <p class="section-tag mb-3">المعلومات الشخصية</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <div class="md:col-span-3">
                        <label class="field-label">الاسم الرباعي <span class="text-red-400">*</span></label>
                        <input type="text" name="full_name" x-model="guestData.full_name" required
                               value="{{ old('full_name') }}" placeholder="أدخل الاسم الرباعي كاملاً..."
                               class="field-input">
                    </div>
                    <div>
                        <label class="field-label">الجنسية</label>
                        <input type="text" name="nationality" x-model="guestData.nationality"
                               value="{{ old('nationality') }}" placeholder="يمني، سعودي..."
                               class="field-input">
                    </div>
                    <div>
                        <label class="field-label">رقم الجوال <span class="text-red-400">*</span></label>
                        <input type="text" name="phone" x-model="guestData.phone" required
                               value="{{ old('phone') }}" placeholder="07X XXXX XXXX"
                               class="field-input">
                    </div>
                    <div>
                        <label class="field-label">المهنة</label>
                        <input type="text" name="occupation" x-model="guestData.occupation"
                               value="{{ old('occupation') }}" placeholder="موظف، تاجر..."
                               class="field-input">
                    </div>
                </div>
            </div>

            {{-- ID info --}}
            <div class="px-5 pb-5">
                <p class="section-tag mb-3">بيانات الهوية</p>
                <div class="bg-slate-50 rounded-xl p-4 grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="field-label">نوع الهوية <span class="text-red-400">*</span></label>
                        <select name="id_type" x-model="guestData.id_type" required class="field-input bg-white">
                            <option value="national_id" {{ old('id_type','national_id')==='national_id'?'selected':'' }}>هوية وطنية</option>
                            <option value="passport"    {{ old('id_type')==='passport'?'selected':'' }}>جواز سفر</option>
                            <option value="residence"   {{ old('id_type')==='residence'?'selected':'' }}>إقامة</option>
                        </select>
                    </div>
                    <div>
                        <label class="field-label">رقم الهوية <span class="text-red-400">*</span></label>
                        <input type="text" name="id_number" x-model="guestData.id_number" required
                               value="{{ old('id_number') }}" class="field-input bg-white">
                    </div>
                    <div>
                        <label class="field-label">جهة إصدار الهوية</label>
                        <input type="text" name="id_issuer" x-model="guestData.id_issuer"
                               value="{{ old('id_issuer') }}" class="field-input bg-white">
                    </div>
                    <div>
                        <label class="field-label">تاريخ الإصدار</label>
                        <input type="date" name="id_issue_date" x-model="guestData.id_issue_date"
                               value="{{ old('id_issue_date') }}" class="field-input bg-white">
                    </div>
                </div>
            </div>

            {{-- Visit info --}}
            <div class="px-5 pb-5">
                <p class="section-tag mb-3">معلومات الزيارة</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="field-label">جهة القدوم</label>
                        <input type="text" name="origin" x-model="guestData.origin"
                               value="{{ old('origin') }}" placeholder="المدينة / المنطقة"
                               class="field-input">
                    </div>
                    <div>
                        <label class="field-label">الغرض من القدوم</label>
                        <input type="text" name="purpose" x-model="guestData.purpose"
                               value="{{ old('purpose') }}" placeholder="سياحة / عمل / علاج..."
                               class="field-input">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="field-label">ملاحظات</label>
                        <textarea name="notes" x-model="guestData.notes" rows="2"
                                  placeholder="أي ملاحظات خاصة بالنزيل..."
                                  class="field-input resize-none">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ID Image --}}
            <div class="px-5 pb-5">
                <p class="section-tag mb-3">صورة الهوية <span class="text-red-400 normal-case" style="font-size:11px">*</span></p>
                <div class="relative border-2 border-dashed rounded-xl transition-all cursor-pointer group"
                     :class="idImageName ? 'border-primary-300 bg-primary-50/30' : 'border-gray-200 hover:border-primary-300 hover:bg-primary-50/20'"
                     @dragover.prevent @drop.prevent="handleIdImageDrop($event)">
                    <input type="file" name="id_image" accept="image/*,.pdf"
                           class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10"
                           @change="handleIdImageChange($event)">
                    <div class="p-5 flex items-center gap-4">
                        <template x-if="!idImagePreview && !idImageName">
                            <div class="flex items-center gap-4 w-full">
                                <div class="w-12 h-12 bg-gray-100 group-hover:bg-primary-100 rounded-xl flex items-center justify-center flex-shrink-0 transition-colors">
                                    <svg class="w-6 h-6 text-gray-400 group-hover:text-primary-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600 group-hover:text-primary-700 transition-colors">اسحب صورة الهوية أو <span class="text-primary-600 underline">اختر ملفاً</span></p>
                                    <p class="text-xs text-gray-400 mt-0.5">JPG, PNG, PDF — بحد أقصى 5MB</p>
                                </div>
                            </div>
                        </template>
                        <template x-if="idImagePreview || idImageName">
                            <div class="flex items-center gap-3 w-full">
                                <img x-show="idImagePreview" :src="idImagePreview" class="w-20 h-14 object-cover rounded-xl border-2 border-primary-200 shadow-sm flex-shrink-0">
                                <div class="w-20 h-14 bg-slate-100 rounded-xl border-2 border-gray-200 flex items-center justify-center flex-shrink-0" x-show="!idImagePreview">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate" x-text="idImageName"></p>
                                    <p class="text-xs text-green-600 mt-1 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        تم الاختيار
                                    </p>
                                    <button type="button" @click.stop="idImagePreview=null; idImageName=''"
                                            class="text-xs text-red-400 hover:text-red-600 mt-0.5 relative z-20">تغيير</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── CARD 3: المرافقون ── --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 bg-violet-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <span class="font-semibold text-gray-800 text-sm">المرافقون
                        <span x-show="companions.length > 0"
                              class="mr-1.5 bg-violet-100 text-violet-700 text-xs font-bold px-2 py-0.5 rounded-full"
                              x-text="companions.length"></span>
                    </span>
                </div>
                <button type="button" @click="addCompanion()"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold px-3.5 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-lg transition shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    إضافة مرافق
                </button>
            </div>

            {{-- Empty state --}}
            <div x-show="companions.length === 0" class="flex flex-col items-center justify-center py-10 text-gray-400">
                <svg class="w-12 h-12 mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <p class="text-sm font-medium">اختياري — لا يوجد مرافقون</p>
                <p class="text-xs mt-1 text-gray-300">اضغط "إضافة مرافق" لإضافة أفراد العائلة</p>
            </div>

            {{-- Companion cards --}}
            <div x-show="companions.length > 0" class="p-4 space-y-3">
                <template x-for="(comp, idx) in companions" :key="idx">
                    <div class="border border-gray-100 rounded-xl overflow-hidden bg-gray-50/50">
                        <div class="flex items-center justify-between px-4 py-2.5 bg-violet-600 text-white">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-white/20 text-xs font-bold flex items-center justify-center" x-text="idx+1"></span>
                                <span class="text-sm font-semibold" x-text="comp.full_name ? comp.full_name : 'مرافق جديد'"></span>
                            </div>
                            <button type="button" @click="removeCompanion(idx)"
                                    class="inline-flex items-center gap-1 text-xs text-white/70 hover:text-white hover:bg-white/15 px-2 py-1 rounded-md transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                حذف
                            </button>
                        </div>
                        <div class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="sm:col-span-2">
                                <label class="field-label">الاسم الكامل <span class="text-red-400">*</span></label>
                                <input type="text" :name="`companions[${idx}][full_name]`" x-model="comp.full_name" required class="field-input">
                            </div>
                            <div>
                                <label class="field-label">صلة القرابة <span class="text-red-400">*</span></label>
                                <select :name="`companions[${idx}][relationship]`" x-model="comp.relationship" required class="field-input bg-white">
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
                                <label class="field-label">الجنسية <span class="text-red-400">*</span></label>
                                <input type="text" :name="`companions[${idx}][nationality]`" x-model="comp.nationality" required class="field-input">
                            </div>
                            <div>
                                <label class="field-label">نوع الهوية</label>
                                <select :name="`companions[${idx}][id_type]`" x-model="comp.id_type" class="field-input bg-white">
                                    <option value="national_id">هوية وطنية</option>
                                    <option value="passport">جواز سفر</option>
                                    <option value="residence">إقامة</option>
                                </select>
                            </div>
                            <div>
                                <label class="field-label">رقم الهوية</label>
                                <input type="text" :name="`companions[${idx}][id_number]`" x-model="comp.id_number" class="field-input">
                            </div>
                            <div>
                                <label class="field-label">جهة الإصدار</label>
                                <input type="text" :name="`companions[${idx}][id_issuer]`" x-model="comp.id_issuer" class="field-input">
                            </div>
                            <div>
                                <label class="field-label">تاريخ الإصدار</label>
                                <input type="date" :name="`companions[${idx}][id_issue_date]`" x-model="comp.id_issue_date" class="field-input">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="field-label">صورة الهوية <span class="text-red-400">*</span></label>
                                <label class="flex items-center gap-3 cursor-pointer border-2 border-dashed border-gray-200 hover:border-violet-400 hover:bg-violet-50/30 rounded-xl p-3 transition group">
                                    <input type="file" :name="`companions[${idx}][id_image]`" accept="image/*,.pdf" required
                                           class="hidden" @change="handleCompanionIdImage($event, idx)">
                                    <div class="w-8 h-8 bg-violet-100 group-hover:bg-violet-200 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors">
                                        <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-gray-600">اختر صورة الهوية</p>
                                        <p class="text-xs text-gray-400">JPG, PNG, PDF</p>
                                    </div>
                                    <img x-show="comp.id_preview && comp.id_preview !== '__pdf__'" :src="comp.id_preview" class="h-10 w-14 rounded-lg object-cover border border-violet-200 flex-shrink-0">
                                    <span x-show="comp.id_preview === '__pdf__'" class="text-xs bg-violet-100 text-violet-700 border border-violet-200 px-2 py-1 rounded-md flex-shrink-0 font-medium">PDF ✓</span>
                                </label>
                            </div>
                            <div x-show="comp.relationship === 'wife'" class="sm:col-span-3">
                                <label class="field-label text-red-600">وثيقة الزواج <span class="text-red-400">*</span></label>
                                <label class="flex items-center gap-3 cursor-pointer border-2 border-dashed border-red-200 hover:border-red-400 hover:bg-red-50/30 rounded-xl p-3 transition group">
                                    <input type="file" :name="`companions[${idx}][marriage_doc]`" accept="image/*,.pdf"
                                           :required="comp.relationship === 'wife'" class="hidden">
                                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
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
    </div>

    {{-- ══════════════════════════════
         RIGHT COLUMN — حجز ثابت
    ══════════════════════════════ --}}
    <div class="lg:sticky lg:top-[52px] lg:self-start space-y-4">

        {{-- Combined Booking Card --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">

            {{-- DATES --}}
            <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <span class="font-semibold text-gray-700 text-sm">مدة الإقامة</span>
            </div>
            <div class="p-4 space-y-3.5">
                <div>
                    <label class="field-label">تاريخ الوصول</label>
                    <div class="flex items-center gap-2 bg-slate-50 border border-gray-200 rounded-lg px-3.5 py-2.5 text-sm text-gray-700">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span x-text="checkInDate" class="font-semibold text-gray-800"></span>
                        <span class="mr-auto text-xs bg-primary-100 text-primary-700 px-2 py-0.5 rounded-md font-semibold">اليوم</span>
                    </div>
                    <input type="hidden" name="check_in_date" :value="checkInDate">
                </div>

                <div>
                    <label class="field-label">عدد الليالي</label>
                    <div class="flex items-center rounded-lg border border-gray-200 overflow-hidden">
                        <button type="button"
                                @click="if(nightsInput > 1){ nightsInput--; updateCheckoutFromNights() }"
                                class="w-10 h-10 flex items-center justify-center bg-gray-50 hover:bg-gray-100 text-gray-500 text-lg font-light border-l border-gray-200 transition flex-shrink-0">−</button>
                        <input type="number" x-model.number="nightsInput" min="1" max="365"
                               @input="updateCheckoutFromNights()"
                               class="flex-1 h-10 text-center text-sm font-bold text-gray-800 outline-none border-none bg-white">
                        <button type="button"
                                @click="nightsInput++; updateCheckoutFromNights()"
                                class="w-10 h-10 flex items-center justify-center bg-gray-50 hover:bg-gray-100 text-gray-500 text-lg font-light border-r border-gray-200 transition flex-shrink-0">+</button>
                    </div>
                </div>

                <div>
                    <label class="field-label">تاريخ المغادرة <span class="text-red-400">*</span></label>
                    <input type="date" name="check_out_date" x-model="checkOutDate" required
                           :min="checkInDate" @change="calcTotal()" class="field-input">
                </div>

                <div>
                    <label class="field-label">وقت الوصول</label>
                    <input type="time" name="check_in_time" x-model="checkInTime" class="field-input">
                </div>
            </div>

            {{-- CALCULATION --}}
            <div x-show="nights > 0 && selectedRoom" class="border-t border-gray-100">
                <div class="px-4 py-3 bg-slate-50">
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                        <span x-text="nights + ' ليلة × ' + formatNumber(effectiveRoomPrice()) + ' ر.ي'"></span>
                        <button type="button" x-show="!showPriceOverride"
                                @click="showPriceOverride = true"
                                class="text-xs text-accent-600 hover:text-accent-700 underline font-medium">تعديل</button>
                        <button type="button" x-show="showPriceOverride"
                                @click="showPriceOverride=false; customPrice=null; calcTotal()"
                                class="text-xs text-red-400 hover:text-red-600 font-medium">إلغاء</button>
                    </div>
                    <div x-show="showPriceOverride" class="mb-2">
                        <input type="number" min="3000" max="100000" step="100"
                               :placeholder="'الأصلي: ' + formatNumber(roomBasePriceFor('YER'))"
                               x-model.number="customPrice" @input="calcTotal()"
                               class="field-input text-sm py-1.5 bg-white">
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">الإجمالي</span>
                        <span class="text-xl font-bold text-primary-800" x-text="formatNumber(totalAmount) + ' ر.ي'"></span>
                    </div>
                </div>
            </div>

            {{-- PAYMENT --}}
            <div class="border-t border-gray-100">
                <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span class="font-semibold text-gray-700 text-sm">الدفع</span>
                </div>
                <div class="p-4 space-y-4">

                    {{-- Payment status --}}
                    <div>
                        <label class="field-label mb-2">حالة الدفع <span class="text-red-400">*</span></label>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="payment_status" value="paid" x-model="paymentStatus" class="peer sr-only">
                                <div class="border border-gray-200 rounded-lg p-3 text-center transition-all cursor-pointer
                                            peer-checked:border-green-500 peer-checked:bg-green-50">
                                    <svg class="w-4 h-4 mx-auto mb-1 text-gray-300 peer-checked:text-green-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <p class="text-xs font-semibold text-gray-500 peer-checked:text-green-700 transition-colors">مدفوع</p>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="payment_status" value="partial" x-model="paymentStatus" class="peer sr-only">
                                <div class="border border-gray-200 rounded-lg p-3 text-center transition-all cursor-pointer
                                            peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                    <svg class="w-4 h-4 mx-auto mb-1 text-gray-300 peer-checked:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <p class="text-xs font-semibold text-gray-500 peer-checked:text-blue-700 transition-colors">جزئي</p>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="payment_status" value="deferred" x-model="paymentStatus" class="peer sr-only">
                                <div class="border border-gray-200 rounded-lg p-3 text-center transition-all cursor-pointer
                                            peer-checked:border-orange-400 peer-checked:bg-orange-50">
                                    <svg class="w-4 h-4 mx-auto mb-1 text-gray-300 peer-checked:text-orange-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <p class="text-xs font-semibold text-gray-500 peer-checked:text-orange-600 transition-colors">آجل</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Partial amount --}}
                    <div x-show="paymentStatus === 'partial'">
                        <label class="field-label">المبلغ المدفوع (ر.ي) <span class="text-red-400">*</span></label>
                        <input type="number" name="paid_amount" x-model="paidAmount" step="0.01" min="0"
                               :max="totalAmount" placeholder="0.00" class="field-input">
                    </div>
                    <template x-if="paymentStatus === 'paid'"><input type="hidden" name="paid_amount" :value="totalAmount"></template>
                    <template x-if="paymentStatus === 'deferred'"><input type="hidden" name="paid_amount" value="0"></template>

                    {{-- Payment method --}}
                    <div>
                        <label class="field-label mb-2">طريقة الدفع</label>
                        <div class="space-y-1.5">
                            @foreach(['cash'=>'نقدي','pos'=>'POS','bank_transfer'=>'تحويل بنكي'] as $val=>$lbl)
                            <label class="flex items-center gap-2.5 cursor-pointer p-2.5 rounded-lg hover:bg-gray-50 transition {{ $val !== 'cash' ? '' : '' }}"
                                   :class="paymentStatus==='deferred' && '{{ $val }}' !== 'cash' ? 'opacity-40 pointer-events-none' : ''">
                                <input type="radio" name="payment_method" value="{{ $val }}" x-model="paymentMethod"
                                       :disabled="paymentStatus==='deferred' && '{{ $val }}' !== 'cash'"
                                       class="w-4 h-4 accent-primary-800">
                                <span class="text-sm text-gray-700 font-medium">{{ $lbl }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Currency note --}}
                    <div>
                        <label class="field-label">ملاحظة العملة (اختياري)</label>
                        <input type="text" name="payment_notes"
                               placeholder="مثال: دفع 100 ر.س بسعر صرف 400"
                               class="field-input">
                    </div>

                    {{-- Bank transfer details --}}
                    <div x-show="paymentMethod === 'bank_transfer'" x-cloak
                         class="bg-blue-50 border border-blue-200 rounded-xl p-3 space-y-3">
                        <p class="text-xs text-blue-700 font-semibold">يجب تقديم سند أو رقم مرجع</p>
                        <div>
                            <label class="field-label">رقم المرجع</label>
                            <input type="text" name="bank_transfer_ref" placeholder="TRF-001..."
                                   class="field-input bg-white">
                        </div>
                        <div x-data="{ receiptName: '' }">
                            <label class="field-label">سند التحويل</label>
                            <label class="flex items-center gap-2.5 cursor-pointer border-2 border-dashed border-blue-300 hover:border-blue-400 hover:bg-blue-100/50 rounded-xl p-3 transition">
                                <input type="file" name="bank_receipt" accept="image/*,.pdf" class="hidden"
                                       @change="receiptName = $event.target.files[0]?.name ?? ''">
                                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-blue-800" x-text="receiptName || 'اختر ملف'"></p>
                                    <p class="text-xs text-green-600 font-medium" x-show="receiptName">تم الاختيار ✓</p>
                                </div>
                            </label>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- SUBMIT BUTTON --}}
        <button type="submit" :disabled="submitting"
                class="w-full py-3.5 bg-primary-800 hover:bg-primary-900 text-white font-bold text-sm rounded-xl transition shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
            <template x-if="!submitting">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
            </template>
            <template x-if="submitting">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            </template>
            <span x-text="submitting ? 'جارٍ تسجيل الدخول...' : 'تأكيد تسجيل الدخول'"></span>
        </button>

        {{-- Nights × price summary (visible on mobile) --}}
        <p class="text-center text-xs text-gray-400 lg:hidden" x-show="totalAmount > 0"
           x-text="nights + ' ليلة × ' + formatNumber(effectiveRoomPrice()) + ' = ' + formatNumber(totalAmount) + ' ر.ي'"></p>

    </div>{{-- end right column --}}

</div>{{-- end main grid --}}
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
