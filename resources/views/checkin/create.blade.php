@extends('layouts.app')
@section('title', 'تسجيل الدخول')
@section('page-title', 'تسجيل الدخول')

@section('content')

@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
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

<div class="space-y-5">

{{-- ═══════════════════════════════════════════════════
     القسم الأول: بيانات النزيل
═══════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-primary-100 flex items-center justify-center">
            <svg class="w-4 h-4 text-primary-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </div>
        <h2 class="font-semibold text-gray-800">بيانات النزيل الرئيسي</h2>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            {{-- الاسم --}}
            <div class="md:col-span-3">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">الاسم الرباعي <span class="text-red-500">*</span></label>
                <input type="text" name="full_name" x-model="guestData.full_name" required
                       value="{{ old('full_name') }}"
                       placeholder="أدخل الاسم الرباعي..."
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
            </div>

            {{-- الجنسية --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">الجنسية</label>
                <input type="text" name="nationality" x-model="guestData.nationality"
                       value="{{ old('nationality') }}" placeholder="مثال: يمني، سعودي..."
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>

            {{-- المهنة --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">المهنة</label>
                <input type="text" name="occupation" x-model="guestData.occupation"
                       value="{{ old('occupation') }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>

            {{-- رقم الجوال --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">رقم الجوال <span class="text-red-500">*</span></label>
                <input type="text" name="phone" x-model="guestData.phone" required
                       value="{{ old('phone') }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>

            {{-- نوع الهوية --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">نوع الهوية <span class="text-red-500">*</span></label>
                <select name="id_type" x-model="guestData.id_type" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                    <option value="national_id" {{ old('id_type','national_id')==='national_id'?'selected':'' }}>هوية وطنية</option>
                    <option value="passport"    {{ old('id_type')==='passport'?'selected':'' }}>جواز سفر</option>
                    <option value="residence"   {{ old('id_type')==='residence'?'selected':'' }}>إقامة</option>
                </select>
            </div>

            {{-- رقم الهوية --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">رقم الهوية <span class="text-red-500">*</span></label>
                <input type="text" name="id_number" x-model="guestData.id_number" required
                       value="{{ old('id_number') }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>

            {{-- جهة الإصدار --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">جهة إصدار الهوية</label>
                <input type="text" name="id_issuer" x-model="guestData.id_issuer"
                       value="{{ old('id_issuer') }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>

            {{-- تاريخ الإصدار --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">تاريخ إصدار الهوية</label>
                <input type="date" name="id_issue_date" x-model="guestData.id_issue_date"
                       value="{{ old('id_issue_date') }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>

            {{-- جهة القدوم --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">جهة القدوم</label>
                <input type="text" name="origin" x-model="guestData.origin"
                       value="{{ old('origin') }}" placeholder="المدينة / المنطقة"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>

            {{-- الغرض --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">الغرض من القدوم</label>
                <input type="text" name="purpose" x-model="guestData.purpose"
                       value="{{ old('purpose') }}" placeholder="سياحة / عمل / علاج..."
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>

            {{-- ملاحظات --}}
            <div class="md:col-span-3">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">ملاحظات</label>
                <textarea name="notes" x-model="guestData.notes" rows="2"
                          placeholder="أي ملاحظات خاصة بالنزيل..."
                          class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none resize-none">{{ old('notes') }}</textarea>
            </div>

            {{-- صورة الهوية --}}
            <div class="md:col-span-3">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">صورة الهوية <span class="text-red-500">*</span></label>
                <div class="border-2 border-dashed border-gray-300 rounded-xl p-5 text-center hover:border-primary-400 transition-colors cursor-pointer relative"
                     @dragover.prevent @drop.prevent="handleIdImageDrop($event)">
                    <input type="file" name="id_image" accept="image/*,.pdf"
                           class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                           @change="handleIdImageChange($event)">
                    <template x-if="!idImagePreview && !idImageName">
                        <div>
                            <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <p class="text-sm text-gray-500">اسحب الصورة هنا أو <span class="text-primary-600 font-medium">اختر ملف</span></p>
                            <p class="text-xs text-gray-400 mt-1">JPG, PNG, PDF — حتى 5MB</p>
                        </div>
                    </template>
                    <template x-if="idImagePreview || idImageName">
                        <div class="flex items-center justify-center gap-3">
                            <img x-show="idImagePreview" :src="idImagePreview" class="w-16 h-12 object-cover rounded-lg border border-gray-200">
                            <div class="text-sm text-right">
                                <p class="font-medium text-gray-700" x-text="idImageName"></p>
                                <p class="text-xs text-green-600 mt-0.5">تم الاختيار ✓</p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════
     القسم الثاني: الغرفة + التواريخ والدفع
═══════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

    {{-- اختيار الغرفة (3/5) --}}
    <div class="lg:col-span-3 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <h2 class="font-semibold text-gray-800">اختيار الغرفة <span class="text-red-500 text-sm">*</span></h2>
            <span x-show="selectedRoom"
                  class="mr-auto px-2.5 py-0.5 bg-green-100 text-green-700 text-xs font-semibold rounded-full"
                  x-text="roomSelectionLabel()"></span>
        </div>
        <div class="p-5">

            {{-- ملخص الغرفة المختارة --}}
            <div x-show="selectedRoom" class="mb-4 p-3 bg-primary-50 border border-primary-200 rounded-xl flex items-center gap-3">
                <div class="w-10 h-10 bg-primary-700 rounded-lg flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                     x-text="suiteBookingType === 'both' ? (selectedRoom?.room_number ?? '') + '+' : (selectedRoom?.room_number ?? '')"></div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-primary-800 text-sm" x-text="roomSelectionLabel()"></div>
                    <div class="text-xs text-primary-600" x-text="(selectedRoom?.room_type_name ?? '') + ' — الطابق ' + (selectedRoom?.floor ?? '')"></div>
                    <div class="text-xs font-medium text-green-700 mt-0.5" x-text="formatNumber(effectiveRoomPrice()) + ' ر.ي / ليلة'"></div>
                </div>
                <button type="button" @click="clearRoomSelection()"
                        class="text-xs text-primary-500 hover:text-primary-700 border border-primary-300 rounded-lg px-2.5 py-1 hover:bg-primary-100 transition">
                    تغيير
                </button>
            </div>

            <div x-show="selectedRoom && linkedInfo?.is_always_linked"
                 class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800">
                الشقة تُحجز كوحدة كاملة — سيشمل الحجز الغرفتين
                <span x-text="(selectedRoom?.room_number ?? '') + ' و ' + (linkedInfo?.linked_number ?? '')"></span>
            </div>

            {{-- فلاتر الطوابق والأنواع --}}
            <div class="mb-4 space-y-2">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-xs font-medium text-gray-500 w-12 shrink-0">الطابق:</span>
                    <button type="button" @click="floorFilter='all'"
                            :class="floorFilter==='all'?'bg-primary-800 text-white':'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            class="px-2.5 py-1 rounded-full text-xs font-medium transition">الكل</button>
                    @foreach($floors as $floor)
                    <button type="button" @click="floorFilter='{{ $floor }}'"
                            :class="floorFilter==='{{ $floor }}'?'bg-primary-800 text-white':'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            class="px-2.5 py-1 rounded-full text-xs font-medium transition">{{ $floor }}</button>
                    @endforeach
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-xs font-medium text-gray-500 w-12 shrink-0">النوع:</span>
                    @foreach(['all'=>'الكل','regular'=>'عادية','double'=>'زوجية','suite'=>'جناح','hall'=>'صالة'] as $key=>$label)
                    <button type="button" @click="typeFilter='{{ $key }}'"
                            :class="typeFilter==='{{ $key }}'?'bg-primary-800 text-white':'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            class="px-2.5 py-1 rounded-full text-xs font-medium transition">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            {{-- شبكة الغرف --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 max-h-80 overflow-y-auto pr-1">
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
                     :class="(roomId=='{{ $room->id }}'||roomId=='{{ $info?$info['linked_id']:'' }}')?'border-primary-600 bg-primary-50':'border-blue-200 bg-blue-50'">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-lg font-bold text-gray-800">{{ $doorLabel }}</span>
                        <span class="text-xs bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded font-medium">جناح</span>
                    </div>
                    <div class="text-xs text-gray-500 truncate mb-1">{{ $room->roomType->name }}</div>
                    <div class="text-xs font-semibold text-green-700 mb-2">{{ number_format($room->roomType->base_price,0) }} ر.ي</div>
                    <div class="flex gap-1">
                        <button type="button"
                                @click="selectRoom({{ json_encode($roomData) }}, {{ json_encode($info) }}, 'a_only')"
                                :class="roomId=='{{ $room->id }}'&&suiteBookingType==='a_only'?'bg-blue-600 text-white border-blue-600':'bg-white text-gray-600 border-gray-300 hover:border-blue-400'"
                                class="flex-1 border rounded-lg py-1 text-xs font-bold transition">A</button>
                        @if($info && $info['linked_available'] && $linkedRoomData)
                        <button type="button"
                                @click="selectRoom({{ json_encode($linkedRoomData) }}, null, 'b_only')"
                                :class="roomId=='{{ $info['linked_id'] }}'?'bg-purple-600 text-white border-purple-600':'bg-white text-gray-600 border-gray-300 hover:border-purple-400'"
                                class="flex-1 border rounded-lg py-1 text-xs font-bold transition">B</button>
                        <button type="button"
                                @click="selectRoom({{ json_encode($roomData) }}, {{ json_encode($info) }}, 'both')"
                                :class="roomId=='{{ $room->id }}'&&suiteBookingType==='both'?'bg-indigo-600 text-white border-indigo-600':'bg-white text-gray-600 border-gray-300 hover:border-indigo-400'"
                                class="flex-1 border rounded-lg py-1 text-xs font-bold transition">A+B</button>
                        @else
                        <div class="flex-1 border border-gray-100 rounded-lg py-1 text-xs text-center text-gray-300 bg-gray-50 cursor-not-allowed">B</div>
                        @endif
                    </div>
                </div>
                @else
                <div x-show="(floorFilter==='all'||floorFilter==='{{ $room->floor }}')&&(typeFilter==='all'||typeFilter==='{{ $roomTypeKey }}')"
                     @click="selectRoom({{ json_encode($roomData) }}, {{ json_encode($info) }})"
                     class="cursor-pointer border-2 rounded-xl p-2.5 transition-all"
                     :class="roomId=='{{ $room->id }}'?'border-primary-600 bg-primary-50':'border-green-200 bg-green-50 hover:border-primary-400'">
                    <div class="flex items-center gap-1 mb-0.5">
                        <span class="text-lg font-bold text-gray-800">{{ $room->room_number }}</span>
                        @if($room->room_sub_type === 'double')
                        <span class="text-xs bg-pink-100 text-pink-700 px-1.5 py-0.5 rounded font-medium">زوجية</span>
                        @elseif($room->room_sub_type === 'apartment')
                        <span class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-medium">شقة</span>
                        @elseif($room->room_sub_type === 'hall')
                        <span class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded font-medium">صالة</span>
                        @endif
                    </div>
                    <div class="text-xs text-gray-500 truncate">{{ $room->roomType->name }}</div>
                    <div class="text-xs text-gray-400">ط{{ $room->floor }}</div>
                    <div class="text-xs font-semibold text-green-700 mt-1">{{ number_format($room->roomType->base_price,0) }} ر.ي</div>
                </div>
                @endif
                @endforeach

                @if($displayRooms->isEmpty())
                <div class="col-span-full text-center py-8 text-gray-400 text-sm">لا توجد غرف متاحة حالياً</div>
                @endif
            </div>
        </div>
    </div>

    {{-- التواريخ والدفع (2/5) --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- التواريخ --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <h2 class="font-semibold text-gray-800">التواريخ</h2>
            </div>
            <div class="p-5 space-y-4">
                {{-- تاريخ الوصول --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">تاريخ الوصول <span class="text-red-500">*</span></label>
                    <div class="w-full border border-gray-200 bg-gray-50 rounded-lg px-4 py-2.5 text-sm text-gray-700 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span x-text="checkInDate"></span>
                        <span class="text-xs text-gray-400">(اليوم)</span>
                    </div>
                    <input type="hidden" name="check_in_date" :value="checkInDate">
                </div>

                {{-- عدد الليالي --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">عدد الليالي</label>
                    <div class="flex items-center gap-3">
                        <input type="number" x-model.number="nightsInput" min="1" max="365"
                               @input="updateCheckoutFromNights()"
                               class="w-24 border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none text-center font-bold">
                        <span class="text-sm text-gray-500">ليلة</span>
                    </div>
                </div>

                {{-- تاريخ المغادرة --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">تاريخ المغادرة <span class="text-red-500">*</span></label>
                    <input type="date" name="check_out_date" x-model="checkOutDate" required
                           :min="checkInDate" @change="calcTotal()"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                </div>

                {{-- وقت الوصول --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">وقت الوصول</label>
                    <input type="time" name="check_in_time" x-model="checkInTime"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
            </div>
        </div>

        {{-- الملخص المالي والدفع --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h2 class="font-semibold text-gray-800">الدفع</h2>
            </div>
            <div class="p-5 space-y-4">

                {{-- ملخص الأرقام --}}
                <div x-show="nights > 0 && selectedRoom" class="grid grid-cols-3 gap-2">
                    <div class="bg-blue-50 rounded-xl p-2.5 text-center">
                        <div class="text-xl font-bold text-blue-700" x-text="nights"></div>
                        <div class="text-xs text-blue-500">ليلة</div>
                    </div>
                    <div class="bg-green-50 rounded-xl p-2.5 text-center">
                        <div class="text-base font-bold text-green-700" x-text="formatNumber(effectiveRoomPrice())"></div>
                        <div class="text-xs text-green-500">ر.ي/ليلة</div>
                    </div>
                    <div class="bg-primary-50 rounded-xl p-2.5 text-center">
                        <div class="text-base font-bold text-primary-700" x-text="formatNumber(totalAmount)"></div>
                        <div class="text-xs text-primary-500">الإجمالي</div>
                    </div>
                </div>

                {{-- تعديل السعر --}}
                <div x-show="nights > 0 && selectedRoom">
                    <button type="button" x-show="!showPriceOverride"
                            @click="showPriceOverride = true"
                            class="w-full px-3 py-2 border border-amber-300 bg-amber-50 text-amber-700 rounded-xl text-xs font-medium hover:bg-amber-100 transition flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        تعديل السعر (تفاوض)
                    </button>
                    <div x-show="showPriceOverride" class="border border-amber-200 bg-amber-50 rounded-xl p-3">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-medium text-amber-800">سعر الليلة (ر.ي)</label>
                            <button type="button" @click="showPriceOverride=false; customPrice=null; calcTotal()"
                                    class="text-xs text-gray-400 hover:text-red-500 underline">إلغاء</button>
                        </div>
                        <input type="number" min="3000" max="100000" step="100"
                               :placeholder="'الأصلي: ' + formatNumber(roomBasePriceFor('YER'))"
                               x-model.number="customPrice" @input="calcTotal()"
                               class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none bg-white">
                        <p class="text-xs text-amber-600 mt-1">النطاق: 3,000 — 100,000 ر.ي</p>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4">

                {{-- حالة الدفع --}}
                <label class="block text-xs font-medium text-gray-600 mb-2">حالة الدفع <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-3 gap-2 mb-4">
                    <label class="cursor-pointer">
                        <input type="radio" name="payment_status" value="paid" x-model="paymentStatus" class="peer sr-only">
                        <div class="border-2 border-gray-200 peer-checked:border-green-500 peer-checked:bg-green-50 rounded-xl p-2 text-center transition-all">
                            <div class="text-xs font-semibold text-gray-700 peer-checked:text-green-700">مدفوع</div>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="payment_status" value="partial" x-model="paymentStatus" class="peer sr-only">
                        <div class="border-2 border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 rounded-xl p-2 text-center transition-all">
                            <div class="text-xs font-semibold text-gray-700 peer-checked:text-blue-700">جزئي</div>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="payment_status" value="deferred" x-model="paymentStatus" class="peer sr-only">
                        <div class="border-2 border-gray-200 peer-checked:border-purple-500 peer-checked:bg-purple-50 rounded-xl p-2 text-center transition-all">
                            <div class="text-xs font-semibold text-gray-700 peer-checked:text-purple-700">آجل</div>
                        </div>
                    </label>
                </div>

                {{-- المبلغ المدفوع (جزئي) --}}
                <div x-show="paymentStatus === 'partial'" class="mb-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">المبلغ المدفوع (ر.ي) <span class="text-red-500">*</span></label>
                    <input type="number" name="paid_amount" x-model="paidAmount" step="0.01" min="0"
                           :max="totalAmount" placeholder="0.00"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
                <template x-if="paymentStatus === 'paid'"><input type="hidden" name="paid_amount" :value="totalAmount"></template>
                <template x-if="paymentStatus === 'deferred'"><input type="hidden" name="paid_amount" value="0"></template>

                {{-- طريقة الدفع --}}
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">طريقة الدفع</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="payment_method" value="cash" x-model="paymentMethod" class="text-primary-600">
                            <span class="text-sm text-gray-700">نقدي</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer" :class="paymentStatus==='deferred'?'opacity-40 cursor-not-allowed':''">
                            <input type="radio" name="payment_method" value="pos" x-model="paymentMethod"
                                   :disabled="paymentStatus==='deferred'" class="text-primary-600">
                            <span class="text-sm text-gray-700">POS</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer" :class="paymentStatus==='deferred'?'opacity-40 cursor-not-allowed':''">
                            <input type="radio" name="payment_method" value="bank_transfer" x-model="paymentMethod"
                                   :disabled="paymentStatus==='deferred'" class="text-primary-600">
                            <span class="text-sm text-gray-700">تحويل</span>
                        </label>
                    </div>
                </div>

                {{-- ملاحظة العملة --}}
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">ملاحظة العملة (اختياري)</label>
                    <input type="text" name="payment_notes"
                           placeholder="مثال: دفع 100 ر.س بسعر صرف 400"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                </div>

                {{-- بيانات التحويل البنكي --}}
                <div x-show="paymentMethod === 'bank_transfer'" x-cloak
                     class="bg-blue-50 border border-blue-200 rounded-xl p-3 space-y-3">
                    <p class="text-xs text-blue-700 font-medium">يجب تقديم سند أو رقم مرجع على الأقل</p>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">رقم المرجع</label>
                        <input type="text" name="bank_transfer_ref" placeholder="TRF-001..."
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
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
</div>

{{-- ═══════════════════════════════════════════════════
     القسم الثالث: المرافقون
═══════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center">
                <svg class="w-4 h-4 text-violet-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <h2 class="font-semibold text-gray-800">المرافقون
                <span x-show="companions.length > 0"
                      class="mr-2 px-2 py-0.5 bg-violet-100 text-violet-700 rounded-full text-xs font-medium"
                      x-text="companions.length + ' مرافق'"></span>
            </h2>
        </div>
        <button type="button" @click="addCompanion()"
                class="flex items-center gap-1.5 px-3 py-2 bg-violet-600 text-white rounded-lg text-sm hover:bg-violet-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            إضافة مرافق
        </button>
    </div>

    <div class="p-6">
        <div x-show="companions.length === 0" class="text-center py-8 text-gray-400">
            <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <p class="text-sm">اختياري — اضغط "إضافة مرافق" لإضافة أفراد العائلة</p>
        </div>

        <div class="space-y-4">
            <template x-for="(comp, idx) in companions" :key="idx">
                <div class="border border-gray-200 rounded-xl p-4 relative">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-violet-100 text-violet-700 text-xs font-bold flex items-center justify-center" x-text="idx+1"></span>
                            <span class="text-sm font-medium text-gray-700">مرافق</span>
                        </div>
                        <button type="button" @click="removeCompanion(idx)"
                                class="text-red-400 hover:text-red-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">الاسم الكامل <span class="text-red-500">*</span></label>
                            <input type="text" :name="`companions[${idx}][full_name]`" x-model="comp.full_name" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">صلة القرابة <span class="text-red-500">*</span></label>
                            <select :name="`companions[${idx}][relationship]`" x-model="comp.relationship" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
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
                            <label class="block text-xs font-medium text-gray-600 mb-1">الجنسية <span class="text-red-500">*</span></label>
                            <input type="text" :name="`companions[${idx}][nationality]`" x-model="comp.nationality" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">نوع الهوية</label>
                            <select :name="`companions[${idx}][id_type]`" x-model="comp.id_type"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                                <option value="national_id">هوية وطنية</option>
                                <option value="passport">جواز سفر</option>
                                <option value="residence">إقامة</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">رقم الهوية</label>
                            <input type="text" :name="`companions[${idx}][id_number]`" x-model="comp.id_number"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">جهة الإصدار</label>
                            <input type="text" :name="`companions[${idx}][id_issuer]`" x-model="comp.id_issuer"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">تاريخ الإصدار</label>
                            <input type="date" :name="`companions[${idx}][id_issue_date]`" x-model="comp.id_issue_date"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">صورة الهوية <span class="text-red-500">*</span></label>
                            <input type="file" :name="`companions[${idx}][id_image]`" accept="image/*,.pdf" required
                                   @change="handleCompanionIdImage($event, idx)"
                                   class="w-full text-sm text-gray-600 file:ml-2 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100">
                            <img x-show="comp.id_preview && comp.id_preview !== '__pdf__'" :src="comp.id_preview" class="mt-1.5 h-12 rounded object-cover border border-gray-200">
                            <p x-show="comp.id_preview === '__pdf__'" class="mt-1 text-xs text-green-600">PDF تم اختياره ✓</p>
                        </div>
                        <div x-show="comp.relationship === 'wife'" class="md:col-span-3">
                            <label class="block text-xs font-medium text-red-600 mb-1">وثيقة الزواج <span class="text-red-500">*</span></label>
                            <input type="file" :name="`companions[${idx}][marriage_doc]`" accept="image/*,.pdf"
                                   :required="comp.relationship === 'wife'"
                                   class="w-full text-sm text-gray-600 file:ml-2 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════
     زر الإرسال
═══════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <button type="submit" :disabled="submitting"
            class="w-full py-3 bg-green-600 text-white rounded-xl font-semibold text-base hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
        <template x-if="!submitting">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
        </template>
        <template x-if="submitting">
            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
        </template>
        <span x-text="submitting ? 'جارٍ الحفظ...' : 'تأكيد تسجيل الدخول'"></span>
    </button>
</div>

</div>{{-- end space-y-5 --}}
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

            // Restore room/payment state after backend validation error
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
