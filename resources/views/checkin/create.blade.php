@extends('layouts.app')
@section('title', 'تسجيل الدخول')
@section('page-title', 'تسجيل الدخول')

@section('content')

@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-200 rounded-2xl p-4 flex items-start gap-3">
    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
    <div>
        <p class="font-semibold text-red-800 text-sm mb-1">يوجد أخطاء، يرجى مراجعتها:</p>
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

{{-- ══════════════════════════════════════════════
     شريط الملخص الثابت
══════════════════════════════════════════════ --}}
<div class="sticky top-0 z-30 bg-white/95 backdrop-blur-sm border-b border-gray-200 shadow-sm -mx-4 sm:-mx-6 px-4 sm:px-6 py-2.5 mb-6 flex items-center gap-2 flex-wrap">
    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-colors"
         :class="selectedRoom ? 'bg-primary-100 text-primary-800' : 'bg-gray-100 text-gray-400'">
        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        <span x-text="selectedRoom ? roomSelectionLabel() : 'اختر غرفة'"></span>
    </div>
    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-colors"
         :class="nights > 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-400'">
        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        <span x-text="nights > 0 ? nights + ' ليلة' : 'المدة'"></span>
    </div>
    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-colors"
         :class="totalAmount > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-400'">
        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span x-text="totalAmount > 0 ? formatNumber(totalAmount) + ' ر.ي' : 'الإجمالي'"></span>
    </div>
    <div class="flex-1"></div>
    <button type="submit" :disabled="submitting"
            class="flex items-center gap-2 px-5 py-2 bg-green-600 text-white rounded-xl text-sm font-semibold hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed shadow-sm">
        <template x-if="!submitting">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </template>
        <template x-if="submitting">
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
        </template>
        <span x-text="submitting ? 'جارٍ الحفظ...' : 'تأكيد الدخول'"></span>
    </button>
</div>

<div class="space-y-8">

{{-- ══════════════════════════════════════════════
     ١ — اختيار الغرفة والحجز
══════════════════════════════════════════════ --}}
<section>
    <div class="flex items-center gap-3 mb-4">
        <div class="w-8 h-8 rounded-full bg-primary-800 text-white flex items-center justify-center text-sm font-bold flex-shrink-0 shadow-sm">١</div>
        <h2 class="font-bold text-gray-800">اختيار الغرفة والحجز</h2>
        <div class="flex-1 h-px bg-gray-200"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

        {{-- شبكة الغرف --}}
        <div class="lg:col-span-3 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span class="font-semibold text-gray-700 text-sm">الغرف المتاحة <span class="text-red-500">*</span></span>
                </div>
                <span x-show="selectedRoom"
                      class="px-3 py-1 bg-primary-800 text-white text-xs font-bold rounded-full"
                      x-text="roomSelectionLabel()"></span>
            </div>

            <div class="p-4">
                {{-- ملخص الغرفة المختارة --}}
                <div x-show="selectedRoom" class="mb-4 p-3 bg-gradient-to-l from-primary-50 to-blue-50 border border-primary-200 rounded-xl flex items-center gap-3">
                    <div class="w-11 h-11 bg-primary-800 rounded-xl flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow"
                         x-text="suiteBookingType === 'both' ? (selectedRoom?.room_number ?? '') + '+' : (selectedRoom?.room_number ?? '')"></div>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-primary-800 text-sm" x-text="roomSelectionLabel()"></div>
                        <div class="text-xs text-gray-500 mt-0.5" x-text="(selectedRoom?.room_type_name ?? '') + ' — الطابق ' + (selectedRoom?.floor ?? '')"></div>
                        <div class="text-sm font-bold text-green-700 mt-0.5" x-text="formatNumber(effectiveRoomPrice()) + ' ر.ي / ليلة'"></div>
                    </div>
                    <button type="button" @click="clearRoomSelection()"
                            class="text-xs text-gray-400 hover:text-red-500 border border-gray-200 hover:border-red-200 rounded-xl px-3 py-1.5 hover:bg-red-50 transition">
                        تغيير
                    </button>
                </div>

                <div x-show="selectedRoom && linkedInfo?.is_always_linked"
                     class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 flex items-start gap-2">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>الشقة تُحجز كوحدة كاملة — سيشمل الحجز الغرفتين
                    <span x-text="(selectedRoom?.room_number ?? '') + ' و ' + (linkedInfo?.linked_number ?? '')"></span></span>
                </div>

                {{-- الفلاتر --}}
                <div class="space-y-2 mb-4 p-3 bg-gray-50 rounded-xl border border-gray-100">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-xs text-gray-400 w-10 shrink-0">الطابق</span>
                        <button type="button" @click="floorFilter='all'"
                                :class="floorFilter==='all'?'bg-primary-800 text-white shadow-sm':'bg-white text-gray-500 border border-gray-200 hover:bg-gray-50'"
                                class="px-3 py-1 rounded-full text-xs font-medium transition">الكل</button>
                        @foreach($floors as $floor)
                        <button type="button" @click="floorFilter='{{ $floor }}'"
                                :class="floorFilter==='{{ $floor }}'?'bg-primary-800 text-white shadow-sm':'bg-white text-gray-500 border border-gray-200 hover:bg-gray-50'"
                                class="px-3 py-1 rounded-full text-xs font-medium transition">{{ $floor }}</button>
                        @endforeach
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-xs text-gray-400 w-10 shrink-0">النوع</span>
                        @foreach(['all'=>'الكل','regular'=>'عادية','double'=>'زوجية','suite'=>'جناح','hall'=>'صالة'] as $key=>$label)
                        <button type="button" @click="typeFilter='{{ $key }}'"
                                :class="typeFilter==='{{ $key }}'?'bg-primary-800 text-white shadow-sm':'bg-white text-gray-500 border border-gray-200 hover:bg-gray-50'"
                                class="px-3 py-1 rounded-full text-xs font-medium transition">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>

                {{-- كروت الغرف --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-72 overflow-y-auto pl-0.5">
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
                         class="border-2 rounded-xl p-2.5 transition-all"
                         :class="(roomId=='{{ $room->id }}'||roomId=='{{ $info?$info['linked_id']:'' }}')?'border-primary-600 bg-primary-50':'border-blue-100 bg-blue-50/50'">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-base font-bold text-gray-800">{{ $doorLabel }}</span>
                            <span class="text-xs bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded-md font-medium">جناح</span>
                        </div>
                        <div class="text-xs text-gray-400 truncate mb-1">{{ $room->roomType->name }}</div>
                        <div class="text-xs font-bold text-green-700 mb-2">{{ number_format($room->roomType->base_price,0) }} ر.ي</div>
                        <div class="flex gap-1">
                            <button type="button"
                                    @click="selectRoom({{ json_encode($roomData) }}, {{ json_encode($info) }}, 'a_only')"
                                    :class="roomId=='{{ $room->id }}'&&suiteBookingType==='a_only'?'bg-blue-600 text-white border-blue-600':'bg-white text-gray-500 border-gray-200 hover:border-blue-400'"
                                    class="flex-1 border rounded-lg py-1 text-xs font-bold transition">A</button>
                            @if($info && $info['linked_available'] && $linkedRoomData)
                            <button type="button"
                                    @click="selectRoom({{ json_encode($linkedRoomData) }}, null, 'b_only')"
                                    :class="roomId=='{{ $info['linked_id'] }}'?'bg-purple-600 text-white border-purple-600':'bg-white text-gray-500 border-gray-200 hover:border-purple-400'"
                                    class="flex-1 border rounded-lg py-1 text-xs font-bold transition">B</button>
                            <button type="button"
                                    @click="selectRoom({{ json_encode($roomData) }}, {{ json_encode($info) }}, 'both')"
                                    :class="roomId=='{{ $room->id }}'&&suiteBookingType==='both'?'bg-indigo-600 text-white border-indigo-600':'bg-white text-gray-500 border-gray-200 hover:border-indigo-400'"
                                    class="flex-1 border rounded-lg py-1 text-xs font-bold transition">A+B</button>
                            @else
                            <div class="flex-1 border border-gray-100 rounded-lg py-1 text-xs text-center text-gray-300 bg-gray-50 cursor-not-allowed">B</div>
                            @endif
                        </div>
                    </div>
                    @else
                    <div x-show="(floorFilter==='all'||floorFilter==='{{ $room->floor }}')&&(typeFilter==='all'||typeFilter==='{{ $roomTypeKey }}')"
                         @click="selectRoom({{ json_encode($roomData) }}, {{ json_encode($info) }})"
                         class="cursor-pointer border-2 rounded-xl p-2.5 transition-all hover:shadow-md hover:-translate-y-0.5"
                         :class="roomId=='{{ $room->id }}'?'border-primary-600 bg-primary-50 shadow-md':'border-gray-200 bg-white hover:border-primary-300'">
                        <div class="flex items-start justify-between mb-1">
                            <span class="text-base font-bold text-gray-800">{{ $room->room_number }}</span>
                            @if($room->room_sub_type === 'double')
                            <span class="text-xs bg-pink-100 text-pink-700 px-1.5 py-0.5 rounded-md font-medium">زوجية</span>
                            @elseif($room->room_sub_type === 'apartment')
                            <span class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-md font-medium">شقة</span>
                            @elseif($room->room_sub_type === 'hall')
                            <span class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-md font-medium">صالة</span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-400 truncate">{{ $room->roomType->name }}</div>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-xs text-gray-400">ط{{ $room->floor }}</span>
                            <span class="text-xs font-bold text-green-700">{{ number_format($room->roomType->base_price,0) }}</span>
                        </div>
                    </div>
                    @endif
                    @endforeach

                    @if($displayRooms->isEmpty())
                    <div class="col-span-full text-center py-8 text-gray-400 text-sm">لا توجد غرف متاحة حالياً</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- التواريخ + الدفع --}}
        <div class="lg:col-span-2 flex flex-col gap-4">

            {{-- التواريخ --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-100 bg-blue-50 flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <span class="font-semibold text-blue-800 text-sm">مدة الإقامة</span>
                </div>
                <div class="p-4 space-y-3">
                    {{-- تاريخ الوصول --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1.5">تاريخ الوصول</label>
                        <div class="flex items-center gap-2 border border-gray-200 bg-gray-50 rounded-xl px-3 py-2.5 text-sm text-gray-700">
                            <svg class="w-4 h-4 text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span x-text="checkInDate" class="font-medium"></span>
                            <span class="text-xs text-gray-400 mr-auto bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full">اليوم</span>
                        </div>
                        <input type="hidden" name="check_in_date" :value="checkInDate">
                    </div>

                    {{-- عدد الليالي --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1.5">عدد الليالي</label>
                        <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden">
                            <button type="button"
                                    @click="if(nightsInput > 1){ nightsInput--; updateCheckoutFromNights() }"
                                    class="w-10 h-10 flex-shrink-0 flex items-center justify-center bg-gray-50 hover:bg-gray-100 text-gray-600 text-xl font-light transition border-l border-gray-200">−</button>
                            <input type="number" x-model.number="nightsInput" min="1" max="365"
                                   @input="updateCheckoutFromNights()"
                                   class="flex-1 h-10 text-center text-sm font-bold text-gray-800 outline-none border-none bg-white">
                            <button type="button"
                                    @click="nightsInput++; updateCheckoutFromNights()"
                                    class="w-10 h-10 flex-shrink-0 flex items-center justify-center bg-gray-50 hover:bg-gray-100 text-gray-600 text-xl font-light transition border-r border-gray-200">+</button>
                        </div>
                    </div>

                    {{-- تاريخ المغادرة --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1.5">تاريخ المغادرة <span class="text-red-500">*</span></label>
                        <input type="date" name="check_out_date" x-model="checkOutDate" required
                               :min="checkInDate" @change="calcTotal()"
                               class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400 outline-none transition">
                    </div>

                    {{-- وقت الوصول --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1.5">وقت الوصول</label>
                        <input type="time" name="check_in_time" x-model="checkInTime"
                               class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-400 outline-none transition">
                    </div>
                </div>
            </div>

            {{-- الدفع --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-100 bg-amber-50 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span class="font-semibold text-amber-800 text-sm">الدفع</span>
                    </div>
                    <span x-show="totalAmount > 0" class="text-sm font-bold text-amber-800" x-text="formatNumber(totalAmount) + ' ر.ي'"></span>
                </div>
                <div class="p-4 space-y-4">

                    {{-- ملخص الحساب --}}
                    <div x-show="nights > 0 && selectedRoom" class="flex items-center gap-2">
                        <div class="flex-1 bg-blue-50 rounded-xl p-2.5 text-center border border-blue-100">
                            <div class="text-lg font-bold text-blue-700" x-text="nights"></div>
                            <div class="text-xs text-blue-400">ليلة</div>
                        </div>
                        <span class="text-gray-300 text-base">×</span>
                        <div class="flex-1 bg-green-50 rounded-xl p-2.5 text-center border border-green-100">
                            <div class="text-sm font-bold text-green-700" x-text="formatNumber(effectiveRoomPrice())"></div>
                            <div class="text-xs text-green-400">ر.ي/ليلة</div>
                        </div>
                        <span class="text-gray-300 text-base">=</span>
                        <div class="flex-1 bg-primary-50 rounded-xl p-2.5 text-center border border-primary-100">
                            <div class="text-sm font-bold text-primary-700" x-text="formatNumber(totalAmount)"></div>
                            <div class="text-xs text-primary-400">الإجمالي</div>
                        </div>
                    </div>

                    {{-- تعديل السعر --}}
                    <div x-show="nights > 0 && selectedRoom">
                        <button type="button" x-show="!showPriceOverride"
                                @click="showPriceOverride = true"
                                class="w-full px-3 py-2 border border-dashed border-amber-300 text-amber-600 rounded-xl text-xs font-medium hover:bg-amber-50 transition flex items-center justify-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            تعديل السعر (تفاوض)
                        </button>
                        <div x-show="showPriceOverride" class="border border-amber-200 bg-amber-50/50 rounded-xl p-3">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-semibold text-amber-800">سعر الليلة (ر.ي)</label>
                                <button type="button" @click="showPriceOverride=false; customPrice=null; calcTotal()"
                                        class="text-xs text-gray-400 hover:text-red-500 transition">✕ إلغاء</button>
                            </div>
                            <input type="number" min="3000" max="100000" step="100"
                                   :placeholder="'الأصلي: ' + formatNumber(roomBasePriceFor('YER'))"
                                   x-model.number="customPrice" @input="calcTotal()"
                                   class="w-full border border-amber-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none bg-white">
                            <p class="text-xs text-amber-500 mt-1">النطاق: 3,000 — 100,000 ر.ي</p>
                        </div>
                    </div>

                    {{-- حالة الدفع --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-2">حالة الدفع <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="payment_status" value="paid" x-model="paymentStatus" class="peer sr-only">
                                <div class="border-2 border-gray-200 peer-checked:border-green-500 peer-checked:bg-green-50 rounded-xl p-2.5 text-center transition-all">
                                    <svg class="w-5 h-5 mx-auto mb-1 text-gray-300 peer-checked:text-green-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <div class="text-xs font-semibold text-gray-600 peer-checked:text-green-700">مدفوع</div>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="payment_status" value="partial" x-model="paymentStatus" class="peer sr-only">
                                <div class="border-2 border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 rounded-xl p-2.5 text-center transition-all">
                                    <svg class="w-5 h-5 mx-auto mb-1 text-gray-300 peer-checked:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <div class="text-xs font-semibold text-gray-600 peer-checked:text-blue-700">جزئي</div>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="payment_status" value="deferred" x-model="paymentStatus" class="peer sr-only">
                                <div class="border-2 border-gray-200 peer-checked:border-purple-500 peer-checked:bg-purple-50 rounded-xl p-2.5 text-center transition-all">
                                    <svg class="w-5 h-5 mx-auto mb-1 text-gray-300 peer-checked:text-purple-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <div class="text-xs font-semibold text-gray-600 peer-checked:text-purple-700">آجل</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- المبلغ المدفوع (جزئي) --}}
                    <div x-show="paymentStatus === 'partial'">
                        <label class="block text-xs font-medium text-gray-500 mb-1.5">المبلغ المدفوع (ر.ي) <span class="text-red-500">*</span></label>
                        <input type="number" name="paid_amount" x-model="paidAmount" step="0.01" min="0"
                               :max="totalAmount" placeholder="0.00"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                    </div>
                    <template x-if="paymentStatus === 'paid'"><input type="hidden" name="paid_amount" :value="totalAmount"></template>
                    <template x-if="paymentStatus === 'deferred'"><input type="hidden" name="paid_amount" value="0"></template>

                    {{-- طريقة الدفع --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-2">طريقة الدفع</label>
                        <div class="flex gap-2">
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="payment_method" value="cash" x-model="paymentMethod" class="peer sr-only">
                                <div class="border border-gray-200 peer-checked:border-green-500 peer-checked:bg-green-50 rounded-xl py-2.5 text-center transition-all text-xs font-medium text-gray-500 peer-checked:text-green-700">نقدي</div>
                            </label>
                            <label class="flex-1 cursor-pointer" :class="paymentStatus==='deferred'?'opacity-40 pointer-events-none':''">
                                <input type="radio" name="payment_method" value="pos" x-model="paymentMethod"
                                       :disabled="paymentStatus==='deferred'" class="peer sr-only">
                                <div class="border border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 rounded-xl py-2.5 text-center transition-all text-xs font-medium text-gray-500 peer-checked:text-blue-700">POS</div>
                            </label>
                            <label class="flex-1 cursor-pointer" :class="paymentStatus==='deferred'?'opacity-40 pointer-events-none':''">
                                <input type="radio" name="payment_method" value="bank_transfer" x-model="paymentMethod"
                                       :disabled="paymentStatus==='deferred'" class="peer sr-only">
                                <div class="border border-gray-200 peer-checked:border-purple-500 peer-checked:bg-purple-50 rounded-xl py-2.5 text-center transition-all text-xs font-medium text-gray-500 peer-checked:text-purple-700">تحويل</div>
                            </label>
                        </div>
                    </div>

                    {{-- ملاحظة العملة --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1.5">ملاحظة العملة (اختياري)</label>
                        <input type="text" name="payment_notes"
                               placeholder="مثال: دفع 100 ر.س بسعر صرف 400"
                               class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                    </div>

                    {{-- بيانات التحويل البنكي --}}
                    <div x-show="paymentMethod === 'bank_transfer'" x-cloak
                         class="bg-blue-50 border border-blue-200 rounded-xl p-3 space-y-3">
                        <p class="text-xs text-blue-700 font-medium">يجب تقديم سند أو رقم مرجع على الأقل</p>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">رقم المرجع</label>
                            <input type="text" name="bank_transfer_ref" placeholder="TRF-001..."
                                   class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none bg-white transition">
                        </div>
                        <div x-data="{ receiptName: '' }">
                            <label class="block text-xs font-medium text-gray-600 mb-1">سند التحويل</label>
                            <label class="flex items-center gap-2 cursor-pointer border-2 border-dashed border-blue-300 rounded-xl p-2.5 hover:border-blue-500 hover:bg-blue-100 transition">
                                <input type="file" name="bank_receipt" accept="image/*,.pdf" class="hidden"
                                       @change="receiptName = $event.target.files[0]?.name ?? ''">
                                <div class="w-7 h-7 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-blue-800" x-text="receiptName || 'اختر ملف'"></p>
                                    <p class="text-xs text-green-600" x-show="receiptName">تم الاختيار ✓</p>
                                </div>
                            </label>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════
     ٢ — بيانات النزيل
══════════════════════════════════════════════ --}}
<section>
    <div class="flex items-center gap-3 mb-4">
        <div class="w-8 h-8 rounded-full bg-primary-800 text-white flex items-center justify-center text-sm font-bold flex-shrink-0 shadow-sm">٢</div>
        <h2 class="font-bold text-gray-800">بيانات النزيل</h2>
        <div class="flex-1 h-px bg-gray-200"></div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden divide-y divide-gray-100">

        {{-- المعلومات الأساسية --}}
        <div class="p-5">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">المعلومات الشخصية</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">الاسم الرباعي <span class="text-red-500">*</span></label>
                    <input type="text" name="full_name" x-model="guestData.full_name" required
                           value="{{ old('full_name') }}"
                           placeholder="أدخل الاسم الرباعي كاملاً..."
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">الجنسية</label>
                    <input type="text" name="nationality" x-model="guestData.nationality"
                           value="{{ old('nationality') }}" placeholder="يمني، سعودي..."
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">المهنة</label>
                    <input type="text" name="occupation" x-model="guestData.occupation"
                           value="{{ old('occupation') }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">رقم الجوال <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" x-model="guestData.phone" required
                           value="{{ old('phone') }}" placeholder="07X XXXX XXXX"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                </div>
            </div>
        </div>

        {{-- بيانات الهوية --}}
        <div class="p-5">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">بيانات الهوية</p>
            <div class="bg-gray-50 rounded-xl p-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">نوع الهوية <span class="text-red-500">*</span></label>
                    <select name="id_type" x-model="guestData.id_type" required
                            class="w-full border border-gray-200 bg-white rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                        <option value="national_id" {{ old('id_type','national_id')==='national_id'?'selected':'' }}>هوية وطنية</option>
                        <option value="passport"    {{ old('id_type')==='passport'?'selected':'' }}>جواز سفر</option>
                        <option value="residence"   {{ old('id_type')==='residence'?'selected':'' }}>إقامة</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">رقم الهوية <span class="text-red-500">*</span></label>
                    <input type="text" name="id_number" x-model="guestData.id_number" required
                           value="{{ old('id_number') }}"
                           class="w-full border border-gray-200 bg-white rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">جهة إصدار الهوية</label>
                    <input type="text" name="id_issuer" x-model="guestData.id_issuer"
                           value="{{ old('id_issuer') }}"
                           class="w-full border border-gray-200 bg-white rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">تاريخ إصدار الهوية</label>
                    <input type="date" name="id_issue_date" x-model="guestData.id_issue_date"
                           value="{{ old('id_issue_date') }}"
                           class="w-full border border-gray-200 bg-white rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                </div>
            </div>
        </div>

        {{-- معلومات الزيارة --}}
        <div class="p-5">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">معلومات الزيارة</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">جهة القدوم</label>
                    <input type="text" name="origin" x-model="guestData.origin"
                           value="{{ old('origin') }}" placeholder="المدينة / المنطقة"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">الغرض من القدوم</label>
                    <input type="text" name="purpose" x-model="guestData.purpose"
                           value="{{ old('purpose') }}" placeholder="سياحة / عمل / علاج..."
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">ملاحظات</label>
                    <textarea name="notes" x-model="guestData.notes" rows="2"
                              placeholder="أي ملاحظات خاصة بالنزيل..."
                              class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none resize-none transition">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- صورة الهوية --}}
        <div class="p-5">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">صورة الهوية <span class="text-red-500">*</span></p>
            <div class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:border-primary-400 hover:bg-primary-50/20 transition-all cursor-pointer relative group"
                 @dragover.prevent @drop.prevent="handleIdImageDrop($event)">
                <input type="file" name="id_image" accept="image/*,.pdf"
                       class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                       @change="handleIdImageChange($event)">
                <template x-if="!idImagePreview && !idImageName">
                    <div>
                        <div class="w-14 h-14 bg-gray-100 group-hover:bg-primary-100 rounded-2xl flex items-center justify-center mx-auto mb-3 transition-colors">
                            <svg class="w-7 h-7 text-gray-400 group-hover:text-primary-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <p class="text-sm text-gray-600">اسحب صورة الهوية هنا أو <span class="text-primary-600 font-semibold">اختر ملفاً</span></p>
                        <p class="text-xs text-gray-400 mt-1">JPG, PNG, PDF — حتى 5MB</p>
                    </div>
                </template>
                <template x-if="idImagePreview || idImageName">
                    <div class="flex items-center justify-center gap-4">
                        <img x-show="idImagePreview" :src="idImagePreview" class="w-24 h-16 object-cover rounded-xl border-2 border-primary-200 shadow">
                        <div class="text-sm text-right">
                            <p class="font-semibold text-gray-800" x-text="idImageName"></p>
                            <p class="text-xs text-green-600 mt-1 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                تم الاختيار
                            </p>
                            <button type="button" @click.stop="idImagePreview=null; idImageName=''"
                                    class="text-xs text-red-400 hover:text-red-600 mt-1 underline">تغيير</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </div>
</section>

{{-- ══════════════════════════════════════════════
     ٣ — المرافقون
══════════════════════════════════════════════ --}}
<section>
    <div class="flex items-center gap-3 mb-4">
        <div class="w-8 h-8 rounded-full bg-primary-800 text-white flex items-center justify-center text-sm font-bold flex-shrink-0 shadow-sm">٣</div>
        <h2 class="font-bold text-gray-800">
            المرافقون
            <span x-show="companions.length > 0"
                  class="mr-1 px-2 py-0.5 bg-violet-100 text-violet-700 rounded-full text-xs font-medium"
                  x-text="companions.length + ' مرافق'"></span>
        </h2>
        <div class="flex-1 h-px bg-gray-200"></div>
        <button type="button" @click="addCompanion()"
                class="flex items-center gap-1.5 px-4 py-2 bg-violet-600 text-white rounded-xl text-sm font-medium hover:bg-violet-700 transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            إضافة مرافق
        </button>
    </div>

    <div x-show="companions.length === 0"
         class="bg-white rounded-2xl border-2 border-dashed border-gray-200 p-8 text-center">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <p class="text-sm text-gray-400">اختياري — اضغط "إضافة مرافق" لإضافة أفراد العائلة</p>
    </div>

    <div class="space-y-4">
        <template x-for="(comp, idx) in companions" :key="idx">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-3.5 bg-violet-50 border-b border-violet-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-violet-200 text-violet-800 text-xs font-bold flex items-center justify-center" x-text="idx+1"></div>
                        <span class="text-sm font-semibold text-violet-800">مرافق</span>
                        <span x-show="comp.full_name" class="text-sm text-violet-500" x-text="'— ' + comp.full_name"></span>
                    </div>
                    <button type="button" @click="removeCompanion(idx)"
                            class="flex items-center gap-1 text-xs text-red-400 hover:text-red-600 hover:bg-red-50 px-2.5 py-1.5 rounded-lg transition border border-transparent hover:border-red-200">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        حذف
                    </button>
                </div>
                <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">الاسم الكامل <span class="text-red-500">*</span></label>
                        <input type="text" :name="`companions[${idx}][full_name]`" x-model="comp.full_name" required
                               class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-400 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">صلة القرابة <span class="text-red-500">*</span></label>
                        <select :name="`companions[${idx}][relationship]`" x-model="comp.relationship" required
                                class="w-full border border-gray-200 bg-white rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-400 outline-none transition">
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
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">الجنسية <span class="text-red-500">*</span></label>
                        <input type="text" :name="`companions[${idx}][nationality]`" x-model="comp.nationality" required
                               class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-400 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">نوع الهوية</label>
                        <select :name="`companions[${idx}][id_type]`" x-model="comp.id_type"
                                class="w-full border border-gray-200 bg-white rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-400 outline-none transition">
                            <option value="national_id">هوية وطنية</option>
                            <option value="passport">جواز سفر</option>
                            <option value="residence">إقامة</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">رقم الهوية</label>
                        <input type="text" :name="`companions[${idx}][id_number]`" x-model="comp.id_number"
                               class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-400 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">جهة الإصدار</label>
                        <input type="text" :name="`companions[${idx}][id_issuer]`" x-model="comp.id_issuer"
                               class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-400 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">تاريخ الإصدار</label>
                        <input type="date" :name="`companions[${idx}][id_issue_date]`" x-model="comp.id_issue_date"
                               class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-400 outline-none transition">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">صورة الهوية <span class="text-red-500">*</span></label>
                        <label class="flex items-center gap-3 cursor-pointer border-2 border-dashed border-gray-200 hover:border-violet-400 hover:bg-violet-50/30 rounded-xl p-3 transition">
                            <input type="file" :name="`companions[${idx}][id_image]`" accept="image/*,.pdf" required
                                   class="hidden" @change="handleCompanionIdImage($event, idx)">
                            <div class="w-9 h-9 bg-violet-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-4.5 h-4.5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-medium text-gray-600">اختر صورة الهوية</p>
                                <p class="text-xs text-gray-400">JPG, PNG, PDF</p>
                            </div>
                            <img x-show="comp.id_preview && comp.id_preview !== '__pdf__'" :src="comp.id_preview" class="h-10 w-14 rounded-xl object-cover border border-violet-200 flex-shrink-0">
                            <span x-show="comp.id_preview === '__pdf__'" class="text-xs bg-red-50 text-red-600 border border-red-200 px-2 py-1 rounded-lg flex-shrink-0">PDF ✓</span>
                        </label>
                    </div>
                    <div x-show="comp.relationship === 'wife'" class="md:col-span-3">
                        <label class="block text-xs font-medium text-red-600 mb-1.5">وثيقة الزواج <span class="text-red-500">*</span></label>
                        <label class="flex items-center gap-3 cursor-pointer border-2 border-dashed border-red-200 hover:border-red-400 hover:bg-red-50/30 rounded-xl p-3 transition">
                            <input type="file" :name="`companions[${idx}][marriage_doc]`" accept="image/*,.pdf"
                                   :required="comp.relationship === 'wife'" class="hidden">
                            <div class="w-9 h-9 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-red-600">رفع وثيقة الزواج</p>
                                <p class="text-xs text-gray-400">JPG, PNG, PDF — مطلوب</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </template>
    </div>
</section>

{{-- زر الإرسال --}}
<div class="pb-4">
    <button type="submit" :disabled="submitting"
            class="w-full py-4 bg-green-600 text-white rounded-2xl font-bold text-base hover:bg-green-700 transition shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2.5">
        <template x-if="!submitting">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
        </template>
        <template x-if="submitting">
            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
        </template>
        <span x-text="submitting ? 'جارٍ تسجيل الدخول...' : 'تأكيد تسجيل الدخول'"></span>
    </button>
</div>

</div>{{-- end space-y-8 --}}
</form>
</div>{{-- end x-data --}}

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
                    roomId:           this.roomId,
                    selectedRoom:     this.selectedRoom,
                    linkedInfo:       this.linkedInfo,
                    suiteBookingType: this.suiteBookingType,
                    checkInDate:      this.checkInDate,
                    checkInTime:      this.checkInTime,
                    checkOutDate:     this.checkOutDate,
                    nightsInput:      this.nightsInput,
                    paymentStatus:    this.paymentStatus,
                    paymentMethod:    this.paymentMethod,
                    paidAmount:       this.paidAmount,
                    customPrice:      this.customPrice,
                    showPriceOverride:this.showPriceOverride,
                    companions:       this.companions.map(c => ({ ...c, id_preview: null })),
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

        removeCompanion(idx) {
            this.companions.splice(idx, 1);
        },

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
            this.selectedRoom = null;
            this.linkedInfo   = null;
            this.roomId       = '';
            this.suiteBookingType = 'a_only';
            this.totalAmount  = 0;
            this.nights       = 0;
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
            if (this.customPrice !== null && this.customPrice !== '') {
                return parseFloat(this.customPrice) || 0;
            }
            if (this.suiteBookingType === 'both') {
                const sp = parseFloat(this.selectedRoom?.suite_price_yer) || 0;
                if (sp > 0) return sp;
                return this.roomBasePriceFor('YER') * 2;
            }
            return this.roomBasePriceFor('YER');
        },

        roomSelectionLabel() {
            if (!this.selectedRoom) return '';
            if (this.suiteBookingType === 'both') {
                return 'جناح ' + this.selectedRoom.room_number + ' + ' + (this.linkedInfo?.linked_number ?? '');
            }
            if (this.linkedInfo?.is_always_linked) {
                return 'شقة ' + this.selectedRoom.room_number + ' + ' + (this.linkedInfo?.linked_number ?? '');
            }
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

        formatNumber(n) {
            return (parseFloat(n) || 0).toLocaleString('ar-YE');
        },

        handleIdImageChange(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.idImageName = file.name;
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (ev) => this.idImagePreview = ev.target.result;
                reader.readAsDataURL(file);
            } else {
                this.idImagePreview = null;
            }
        },

        handleIdImageDrop(e) {
            const file = e.dataTransfer.files[0];
            if (!file) return;
            const input = document.querySelector('input[name="id_image"]');
            if (input) {
                const dt = new DataTransfer(); dt.items.add(file); input.files = dt.files;
            }
            this.handleIdImageChange({ target: { files: [file] } });
        },

        handleCompanionIdImage(e, idx) {
            const file = e.target.files[0];
            if (!file) return;
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (ev) => this.companions[idx].id_preview = ev.target.result;
                reader.readAsDataURL(file);
            } else {
                this.companions[idx].id_preview = '__pdf__';
            }
        },
    }
}
</script>
@endpush
