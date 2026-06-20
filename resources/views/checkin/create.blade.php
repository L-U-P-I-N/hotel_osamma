@extends('layouts.app')
@section('title', 'تسجيل الدخول')
@section('page-title', 'تسجيل الدخول')

@section('content')
<div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3 text-sm text-green-800">
    <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
    <span><strong>تسجيل دخول فوري</strong> — النزيل موجود الآن، ستصبح الغرفة <strong>مشغولة</strong></span>
</div>
<div x-data="checkInWizard()" x-init="init()">

<!-- Step Indicator -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
    <div class="flex items-center justify-between relative">
        <div class="absolute top-5 right-0 left-0 h-0.5 bg-gray-200 z-0">
            <div class="h-full bg-primary-600 transition-all duration-500" :style="`width: ${(currentStep-1)/4*100}%`"></div>
        </div>
        <template x-for="(label, i) in stepLabels" :key="i">
            <div class="flex flex-col items-center z-10">
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300"
                     :class="currentStep > i+1 ? 'bg-green-500 text-white' : currentStep === i+1 ? 'bg-primary-800 text-white ring-4 ring-primary-200' : 'bg-gray-200 text-gray-500'">
                    <template x-if="currentStep > i+1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </template>
                    <template x-if="currentStep <= i+1">
                        <span x-text="i+1"></span>
                    </template>
                </div>
                <span class="text-xs mt-1.5 font-medium" :class="currentStep === i+1 ? 'text-primary-800' : 'text-gray-400'" x-text="label"></span>
            </div>
        </template>
    </div>
</div>

<form id="checkInForm" method="POST" action="{{ route('checkin.store') }}" enctype="multipart/form-data"
      autocomplete="off" @submit="handleSubmit($event)">
@csrf

<!-- STEP 1: Guest Details -->
<div x-show="currentStep === 1" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-5 pb-3 border-b border-gray-100">بيانات النزيل</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">الاسم الرباعي <span class="text-red-500">*</span></label>
            <input type="text" name="full_name" x-model="guestData.full_name" required
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">الجنسية</label>
            <input type="text" name="nationality" x-model="guestData.nationality" placeholder="مثال: يمني، سعودي..."
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">المهنة</label>
            <input type="text" name="occupation" x-model="guestData.occupation"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">جهة القدوم</label>
            <input type="text" name="origin" x-model="guestData.origin" placeholder="المدينة / المنطقة"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">الغرض من القدوم</label>
            <input type="text" name="purpose" x-model="guestData.purpose" placeholder="سياحة / عمل / علاج..."
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">نوع الهوية <span class="text-red-500">*</span></label>
            <select name="id_type" x-model="guestData.id_type" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                <option value="national_id">هوية وطنية</option>
                <option value="passport">جواز سفر</option>
                <option value="residence">إقامة</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">رقم الهوية <span class="text-red-500">*</span></label>
            <input type="text" name="id_number" x-model="guestData.id_number" required
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">جهة إصدار الهوية</label>
            <input type="text" name="id_issuer" x-model="guestData.id_issuer"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">تاريخ الإصدار</label>
            <input type="date" name="id_issue_date" x-model="guestData.id_issue_date"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">رقم الجوال <span class="text-red-500">*</span></label>
            <input type="text" name="phone" x-model="guestData.phone" required
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">ملاحظات</label>
            <textarea name="notes" x-model="guestData.notes" rows="2"
                      class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none resize-none"></textarea>
        </div>

        <!-- ID Image Upload -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">صورة الهوية <span class="text-red-500">*</span></label>
            <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-primary-400 transition-colors cursor-pointer relative"
                 @dragover.prevent @drop.prevent="handleIdImageDrop($event)">
                <input type="file" name="id_image" accept="image/*,.pdf" class="absolute inset-0 opacity-0 cursor-pointer"
                       @change="handleIdImageChange($event)">
                <template x-if="!idImagePreview">
                    <div>
                        <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <p class="text-sm text-gray-500">اسحب الصورة هنا أو <span class="text-primary-600 font-medium">اختر ملف</span></p>
                        <p class="text-xs text-gray-400 mt-1">JPG, PNG, PDF — حتى 5MB</p>
                    </div>
                </template>
                <template x-if="idImagePreview">
                    <div class="flex items-center gap-3">
                        <img :src="idImagePreview" class="w-20 h-16 object-cover rounded-lg">
                        <div class="text-sm text-gray-600 text-right">
                            <p class="font-medium" x-text="idImageName"></p>
                            <p class="text-xs text-green-600 mt-0.5">تم الرفع ✓</p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<!-- STEP 2: Companions -->
<div x-show="currentStep === 2" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center justify-between mb-5 pb-3 border-b border-gray-100">
        <h2 class="text-lg font-semibold text-gray-800">المرافقون
            <span x-show="companions.length > 0" class="mr-2 px-2 py-0.5 bg-primary-100 text-primary-800 rounded-full text-sm font-normal" x-text="companions.length + ' مرافق'"></span>
        </h2>
        <button type="button" @click="addCompanion()"
                class="flex items-center gap-2 px-4 py-2 bg-primary-800 text-white rounded-lg text-sm hover:bg-primary-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            إضافة مرافق
        </button>
    </div>

    <div x-show="companions.length === 0" class="text-center py-10 text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <p class="text-sm">لا يوجد مرافقون. انقر "إضافة مرافق" لإضافة أفراد العائلة.</p>
    </div>

    <div class="space-y-4">
        <template x-for="(comp, idx) in companions" :key="idx">
            <div class="border border-gray-200 rounded-xl p-4 relative">
                <button type="button" @click="removeCompanion(idx)"
                        class="absolute top-3 left-3 text-red-400 hover:text-red-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <h4 class="font-medium text-gray-700 mb-3 text-sm" x-text="`مرافق ${idx+1}`"></h4>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">الاسم الكامل *</label>
                        <input type="text" :name="`companions[${idx}][full_name]`" x-model="comp.full_name" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">صلة القرابة *</label>
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

                    <!-- ID Image -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">صورة الهوية <span class="text-red-500">*</span></label>
                        <input type="file" :name="`companions[${idx}][id_image]`" accept="image/*,.pdf" required
                               @change="handleCompanionIdImage($event, idx)"
                               class="w-full text-sm text-gray-600 file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                        <img x-show="comp.id_preview && comp.id_preview !== '__pdf__'" :src="comp.id_preview" class="mt-2 h-16 rounded object-cover">
                        <p x-show="comp.id_preview === '__pdf__'" class="mt-1 text-xs text-green-600">تم اختيار ملف PDF</p>
                    </div>

                    <!-- Marriage Doc (wife only) -->
                    <div x-show="comp.relationship === 'wife'" class="md:col-span-3">
                        <label class="block text-xs font-medium text-red-600 mb-1">وثيقة الزواج (مطلوبة للزوجة) *</label>
                        <input type="file" :name="`companions[${idx}][marriage_doc]`" accept="image/*,.pdf"
                               :required="comp.relationship === 'wife'"
                               class="w-full text-sm text-gray-600 file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

<!-- STEP 3: Room Selection -->
<div x-show="currentStep === 3" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-100">اختيار الغرفة</h2>

    <!-- Selected room summary -->
    <div x-show="selectedRoom" class="mb-4 p-4 bg-primary-50 border border-primary-200 rounded-xl flex items-center gap-4">
        <div class="w-12 h-12 bg-primary-600 rounded-xl flex items-center justify-center text-white font-bold text-lg" x-text="suiteBookingType === 'both' ? selectedRoom?.room_number + '+' + (linkedInfo?.linked_number ?? '') : selectedRoom?.room_number"></div>
        <div>
            <div class="font-semibold text-primary-800" x-text="roomSelectionLabel()"></div>
            <div class="text-sm text-primary-600" x-text="selectedRoom?.room_type_name + ' - الطابق ' + selectedRoom?.floor"></div>
            <div class="text-sm font-medium text-primary-700 mt-0.5" x-text="formatNumber(effectiveRoomPrice()) + ' ر.ي / ليلة'"></div>
        </div>
        <button type="button" @click="clearRoomSelection()" class="mr-auto text-primary-400 hover:text-primary-600 text-xs">تغيير</button>
    </div>

    <!-- Apartment notice -->
    <div x-show="selectedRoom && linkedInfo?.is_always_linked" class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800">
        <strong>الشقة تُحجز دائماً كوحدة كاملة</strong> — سيشمل الحجز الغرفتين
        <span x-text="selectedRoom?.room_number + ' و ' + (linkedInfo?.linked_number ?? '')"></span> تلقائياً.
    </div>

    <input type="hidden" name="room_id" x-model="roomId">
    <input type="hidden" name="suite_booking_type" x-model="suiteBookingType">

    <!-- Filters -->
    <div class="mb-5 space-y-2">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium text-gray-500 w-12 flex-shrink-0">الطابق:</span>
            <button type="button" @click="floorFilter = 'all'"
                    :class="floorFilter === 'all' ? 'bg-primary-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-3 py-1 rounded-full text-xs font-medium transition">الكل</button>
            @foreach($floors as $floor)
            <button type="button" @click="floorFilter = '{{ $floor }}'"
                    :class="floorFilter === '{{ $floor }}' ? 'bg-primary-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-3 py-1 rounded-full text-xs font-medium transition">{{ $floor }}</button>
            @endforeach
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium text-gray-500 w-12 flex-shrink-0">النوع:</span>
            <button type="button" @click="typeFilter = 'all'"
                    :class="typeFilter === 'all' ? 'bg-primary-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-3 py-1 rounded-full text-xs font-medium transition">الكل</button>
            <button type="button" @click="typeFilter = 'regular'"
                    :class="typeFilter === 'regular' ? 'bg-primary-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-3 py-1 rounded-full text-xs font-medium transition">عادية</button>
            <button type="button" @click="typeFilter = 'double'"
                    :class="typeFilter === 'double' ? 'bg-primary-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-3 py-1 rounded-full text-xs font-medium transition">زوجية</button>
            <button type="button" @click="typeFilter = 'suite'"
                    :class="typeFilter === 'suite' ? 'bg-primary-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-3 py-1 rounded-full text-xs font-medium transition">جناح</button>
            <button type="button" @click="typeFilter = 'hall'"
                    :class="typeFilter === 'hall' ? 'bg-primary-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-3 py-1 rounded-full text-xs font-medium transition">صالة</button>
        </div>
    </div>

    <!-- Room Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
        @foreach($displayRooms as $room)
        @php
            $info = $linkedAvailability[$room->id] ?? null;
            $roomData = [
                'id'             => $room->id,
                'room_number'    => $room->room_number,
                'floor'          => $room->floor,
                'base_price'     => (float)$room->roomType->base_price,
                'prices'         => $room->pricesArray(),
                'room_type_name' => $room->roomType->name,
                'sub_type'       => $room->room_sub_type,
            ];
            $roomTypeKey = match($room->room_sub_type) {
                'suite_a', 'suite_b' => 'suite',
                'hall'               => 'hall',
                'double'             => 'double',
                default              => 'regular',
            };
        @endphp

        @if($room->room_sub_type === 'suite_a')
        @php
            $doorLabel = rtrim($room->room_number, 'AB');
            $linkedRoomData = $info ? [
                'id'             => $info['linked_id'],
                'room_number'    => $info['linked_number'],
                'floor'          => $room->floor,
                'base_price'     => (float)$room->roomType->base_price,
                'prices'         => $room->pricesArray(),
                'room_type_name' => $room->roomType->name,
                'sub_type'       => 'suite_b',
            ] : null;
        @endphp
        {{-- Suite door card with inline A / B / A+B selector --}}
        <div x-show="(floorFilter === 'all' || floorFilter === '{{ $room->floor }}') && (typeFilter === 'all' || typeFilter === 'suite')"
             class="border-2 rounded-xl p-3 transition-all duration-200"
             :class="(roomId == '{{ $room->id }}' || roomId == '{{ $info ? $info['linked_id'] : '' }}') ? 'border-primary-600 bg-primary-50' : 'border-blue-200 bg-blue-50'">
            <div class="flex items-center justify-between mb-1.5">
                <div class="flex items-center gap-1.5">
                    <span class="text-xl font-bold text-gray-800">{{ $doorLabel }}</span>
                    <span class="text-xs bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded font-medium">جناح</span>
                </div>
                <span class="text-xs text-gray-400">ط{{ $room->floor }}</span>
            </div>
            <div class="text-xs text-gray-500 mb-1">{{ $room->roomType->name }}</div>
            <div class="text-xs font-semibold text-green-700 mb-2.5">{{ number_format($room->roomType->base_price,0) }} ر.ي / ليلة</div>
            <div class="flex gap-1">
                {{-- A only --}}
                <button type="button"
                        @click="selectRoom({{ json_encode($roomData) }}, {{ json_encode($info) }}, 'a_only')"
                        :class="roomId == '{{ $room->id }}' && suiteBookingType === 'a_only' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:border-blue-400 hover:text-blue-700'"
                        class="flex-1 border rounded-lg py-1.5 text-xs font-bold transition text-center">A</button>
                @if($info && $info['linked_available'] && $linkedRoomData)
                {{-- B only --}}
                <button type="button"
                        @click="selectRoom({{ json_encode($linkedRoomData) }}, null, 'b_only')"
                        :class="roomId == '{{ $info['linked_id'] }}' ? 'bg-purple-600 text-white border-purple-600' : 'bg-white text-gray-600 border-gray-300 hover:border-purple-400 hover:text-purple-700'"
                        class="flex-1 border rounded-lg py-1.5 text-xs font-bold transition text-center">B</button>
                {{-- Both --}}
                <button type="button"
                        @click="selectRoom({{ json_encode($roomData) }}, {{ json_encode($info) }}, 'both')"
                        :class="roomId == '{{ $room->id }}' && suiteBookingType === 'both' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400 hover:text-indigo-700'"
                        class="flex-1 border rounded-lg py-1.5 text-xs font-bold transition text-center">A+B</button>
                @else
                <div class="flex-1 border border-gray-100 rounded-lg py-1.5 text-xs text-center text-gray-300 bg-gray-50 cursor-not-allowed" title="البوابة B محجوزة">B</div>
                @endif
            </div>
        </div>

        @else
        {{-- Regular / Hall / Apartment card --}}
        <div x-show="(floorFilter === 'all' || floorFilter === '{{ $room->floor }}') && (typeFilter === 'all' || typeFilter === '{{ $roomTypeKey }}')"
             @click="selectRoom({{ json_encode($roomData) }}, {{ json_encode($info) }})"
             class="cursor-pointer border-2 rounded-xl p-3 transition-all duration-200"
             :class="roomId == '{{ $room->id }}' ? 'border-primary-600 bg-primary-50' : 'border-green-200 bg-green-50 hover:border-primary-400'">
            <div class="flex items-center gap-1">
                <div class="text-xl font-bold text-gray-800">{{ $room->room_number }}</div>
                @if($room->room_sub_type === 'double')
                    <span class="text-xs bg-pink-100 text-pink-700 px-1.5 py-0.5 rounded font-medium">زوجية</span>
                @elseif($room->room_sub_type === 'apartment')
                    <span class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-medium">شقة</span>
                @elseif($room->room_sub_type === 'hall')
                    <span class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded font-medium">صالة</span>
                @endif
            </div>
            <div class="text-xs text-gray-500 mt-0.5">{{ $room->roomType->name }}</div>
            <div class="text-xs text-gray-400">الطابق {{ $room->floor }}</div>
            <div class="text-xs font-semibold text-green-700 mt-1">{{ number_format($room->roomType->base_price,0) }} ر.ي</div>
            @if($info && $info['linked_available'])
                <div class="text-xs text-blue-600 mt-0.5">+ {{ $info['linked_number'] }} متاحة</div>
            @endif
        </div>
        @endif
        @endforeach

        @if($displayRooms->isEmpty())
        <div class="col-span-full text-center py-10 text-gray-400">
            <p class="text-sm">لا توجد غرف متاحة حالياً</p>
        </div>
        @endif
    </div>
</div>

<!-- STEP 4: Dates & Payment -->
<div x-show="currentStep === 4" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-5 pb-3 border-b border-gray-100">التواريخ والدفع</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">تاريخ الوصول <span class="text-red-500">*</span></label>
            <template x-if="BOOKING_MODE === 'checkin'">
                <div>
                    <div class="w-full border border-gray-200 bg-gray-50 rounded-lg px-4 py-2.5 text-sm text-gray-700 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span x-text="checkInDate"></span>
                        <span class="text-xs text-gray-400 mr-1">(اليوم)</span>
                    </div>
                    <input type="hidden" name="check_in_date" :value="checkInDate">
                </div>
            </template>
            <template x-if="BOOKING_MODE !== 'checkin'">
                <input type="date" name="check_in_date" x-model="checkInDate" required
                       :min="today()" @change="calcTotal()"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </template>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">عدد الليالي</label>
            <div class="flex items-center gap-2">
                <input type="number" x-model.number="nightsInput" min="1" max="365"
                       @input="updateCheckoutFromNights()"
                       class="w-24 border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none text-center font-bold">
                <span class="text-sm text-gray-500">ليلة</span>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">تاريخ المغادرة <span class="text-red-500">*</span></label>
            <input type="date" name="check_out_date" x-model="checkOutDate" required
                   :min="checkInDate || today()" @change="calcTotal()"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">وقت الوصول</label>
            <input type="time" name="check_in_time" x-model="checkInTime"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
        </div>

        <input type="hidden" name="currency" value="YER">

        <!-- Summary box -->
        <div x-show="nights > 0" class="md:col-span-2 space-y-3">
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-blue-50 rounded-xl p-3 text-center">
                    <div class="text-2xl font-bold text-blue-700" x-text="nights"></div>
                    <div class="text-xs text-blue-500 mt-0.5">ليلة</div>
                </div>
                <div class="bg-green-50 rounded-xl p-3 text-center relative">
                    <div class="text-2xl font-bold text-green-700" x-text="formatNumber(effectiveRoomPrice())"></div>
                    <div class="text-xs text-green-500 mt-0.5">ر.ي / ليلة</div>
                    <div x-show="customPrice !== null" class="text-xs text-amber-600 mt-0.5">(معدَّل)</div>
                </div>
                <div class="bg-primary-50 rounded-xl p-3 text-center">
                    <div class="text-2xl font-bold text-primary-700" x-text="formatNumber(totalAmount)"></div>
                    <div class="text-xs text-primary-500 mt-0.5">الإجمالي ر.ي</div>
                </div>
            </div>

            <!-- Price override (per selected currency) -->
            <div class="border border-amber-200 bg-amber-50 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-sm font-medium text-amber-800">تعديل سعر الليلة (تفاوض)</label>
                    <button type="button" x-show="customPrice !== null"
                            @click="customPrice = null; calcTotal()"
                            class="text-xs text-gray-400 hover:text-red-500 underline">
                        استعادة السعر الأصلي (<span x-text="formatNumber(roomBasePriceFor('YER'))"></span> ر.ي)
                    </button>
                </div>
                <div class="flex items-center gap-3">
                    <input type="number" min="0" step="100"
                           :placeholder="'السعر الأصلي: ' + formatNumber(roomBasePriceFor('YER'))"
                           x-model.number="customPrice"
                           @input="calcTotal()"
                           class="flex-1 border border-amber-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 outline-none bg-white">
                    <span class="text-sm text-amber-700 font-medium whitespace-nowrap">ر.ي / ليلة</span>
                </div>
                <p class="text-xs text-amber-600 mt-1.5">اتركه فارغاً لاستخدام السعر الأصلي للغرفة بالريال اليمني</p>
            </div>
        </div>

        <input type="hidden" name="total_amount" :value="totalAmount">
        <input type="hidden" name="price_per_night" :value="effectiveRoomPrice()">

        <!-- Payment Status -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">حالة الدفع <span class="text-red-500">*</span></label>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <label class="relative cursor-pointer">
                    <input type="radio" name="payment_status" value="paid" x-model="paymentStatus" class="peer sr-only">
                    <div class="border-2 border-gray-200 peer-checked:border-green-500 peer-checked:bg-green-50 rounded-xl p-3 text-center transition-all">
                        <div class="font-semibold text-sm text-gray-700 peer-checked:text-green-700">مدفوع كامل</div>
                        <div class="text-xs text-gray-400 mt-0.5">الدفع الكامل عند الوصول</div>
                    </div>
                </label>
                <label class="relative cursor-pointer">
                    <input type="radio" name="payment_status" value="partial" x-model="paymentStatus" class="peer sr-only">
                    <div class="border-2 border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 rounded-xl p-3 text-center transition-all">
                        <div class="font-semibold text-sm text-gray-700">دفعة جزئية</div>
                        <div class="text-xs text-gray-400 mt-0.5">دفع جزء من المبلغ</div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Payment Details (paid or partial) -->
        <div x-show="paymentStatus === 'paid' || paymentStatus === 'partial'" class="md:col-span-2 space-y-4">
            <div x-show="paymentStatus === 'partial'">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">المبلغ المدفوع (ر.ي) <span class="text-red-500">*</span></label>
                <input type="number" name="paid_amount" x-model="paidAmount" step="0.01" min="0"
                       :max="totalAmount" placeholder="0.00"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <template x-if="paymentStatus === 'paid'">
                <input type="hidden" name="paid_amount" :value="totalAmount">
            </template>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">طريقة الدفع</label>
                <div class="flex gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="cash" x-model="paymentMethod" class="text-primary-600">
                        <span class="text-sm">نقدي</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="bank_transfer" x-model="paymentMethod" class="text-primary-600">
                        <span class="text-sm">تحويل بنكي</span>
                    </label>
                </div>
            </div>

            <!-- Currency Notes (optional) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">ملاحظة العملة (اختياري)</label>
                <input type="text" name="payment_notes"
                       placeholder="مثال: دفع 100 ر.س بسعر صرف 400 = 40,000 ر.ي"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>

            <!-- Bank Transfer Fields -->
            <div x-show="paymentMethod === 'bank_transfer'" x-cloak
                 class="bg-blue-50 border border-blue-200 rounded-xl p-4 space-y-3">
                <h4 class="font-medium text-blue-800 text-sm">بيانات التحويل البنكي</h4>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">رقم السند / المرجع</label>
                    <input type="text" name="bank_transfer_ref" placeholder="أدخل رقم السند"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
                <div x-data="{ receiptName: '' }">
                    <label class="block text-xs font-medium text-gray-600 mb-1">سند التحويل البنكي (صورة/PDF)</label>
                    <label class="flex items-center gap-3 cursor-pointer border-2 border-dashed border-blue-300 rounded-xl p-3 hover:border-blue-500 hover:bg-blue-100 transition">
                        <input type="file" name="bank_receipt" accept="image/*,.pdf" class="hidden"
                               @change="receiptName = $event.target.files[0]?.name ?? ''">
                        <div class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-blue-800" x-text="receiptName || 'انقر لرفع سند التحويل'"></p>
                            <p class="text-xs text-blue-500 mt-0.5" x-show="!receiptName">JPG, PNG, PDF — حتى 10MB</p>
                            <p class="text-xs text-green-600 mt-0.5" x-show="receiptName">تم اختيار الملف ✓</p>
                        </div>
                    </label>
                </div>
            </div>

        </div>

    </div>
</div>

<!-- STEP 5: Confirm -->
<div x-show="currentStep === 5" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-5 pb-3 border-b border-gray-100">مراجعة وتأكيد البيانات</h2>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <div>
                <p class="font-semibold text-red-800 text-sm mb-1">يوجد أخطاء في البيانات، يرجى مراجعتها:</p>
                <ul class="text-red-700 text-sm space-y-0.5 list-disc list-inside">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-gray-50 rounded-xl p-4">
            <h3 class="font-semibold text-gray-700 text-sm mb-3">بيانات النزيل</h3>
            <div class="space-y-1.5 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">الاسم:</span><span class="font-medium" x-text="guestData.full_name || '-'"></span></div>
                <div class="flex justify-between"><span class="text-gray-500">الجنسية:</span><span x-text="guestData.nationality || '-'"></span></div>
                <div class="flex justify-between"><span class="text-gray-500">نوع الهوية:</span><span x-text="idTypeLabel[guestData.id_type] || '-'"></span></div>
                <div class="flex justify-between"><span class="text-gray-500">رقم الهوية:</span><span x-text="guestData.id_number || '-'"></span></div>
                <div class="flex justify-between"><span class="text-gray-500">الجوال:</span><span x-text="guestData.phone || '-'"></span></div>
            </div>
        </div>
        <div class="bg-gray-50 rounded-xl p-4">
            <h3 class="font-semibold text-gray-700 text-sm mb-3">بيانات الحجز</h3>
            <div class="space-y-1.5 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">الغرفة:</span><span class="font-medium" x-text="roomSelectionLabel() || '-'"></span></div>
                <div class="flex justify-between"><span class="text-gray-500">النوع:</span><span x-text="selectedRoom?.room_type_name || '-'"></span></div>
                <div class="flex justify-between"><span class="text-gray-500">تاريخ الدخول:</span><span x-text="checkInDate || '-'"></span></div>
                <div x-show="checkInTime" class="flex justify-between"><span class="text-gray-500">وقت الوصول:</span><span x-text="checkInTime"></span></div>
                <div class="flex justify-between"><span class="text-gray-500">تاريخ الخروج:</span><span x-text="checkOutDate || '-'"></span></div>
                <div class="flex justify-between"><span class="text-gray-500">عدد الليالي:</span><span x-text="nights"></span></div>
            </div>
        </div>
        <div class="bg-gray-50 rounded-xl p-4">
            <h3 class="font-semibold text-gray-700 text-sm mb-3">المرافقون</h3>
            <div class="text-sm" x-text="companions.length > 0 ? companions.length + ' مرافق' : 'لا يوجد مرافقون'"></div>
        </div>
        <div class="bg-primary-50 rounded-xl p-4 border border-primary-200">
            <h3 class="font-semibold text-primary-800 text-sm mb-3">ملخص المدفوعات</h3>
            <div class="space-y-1.5 text-sm">
                <div class="flex justify-between"><span class="text-gray-600">الإجمالي:</span><span class="font-bold text-primary-800" x-text="formatNumber(totalAmount) + ' ر.ي'"></span></div>
                <div class="flex justify-between"><span class="text-gray-600">المدفوع:</span><span class="font-medium" x-text="formatNumber(effectivePaid) + ' ر.ي'"></span></div>
                <div class="flex justify-between border-t border-primary-200 pt-1.5 mt-1.5"><span class="text-gray-600">المتبقي:</span><span class="font-bold" :class="totalAmount - effectivePaid > 0 ? 'text-red-600' : 'text-green-600'" x-text="formatNumber(totalAmount - effectivePaid) + ' ر.ي'"></span></div>
            </div>
        </div>
    </div>

    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm text-yellow-800 mb-4">
        يرجى مراجعة جميع البيانات قبل التأكيد. لا يمكن تعديل البيانات بعد التسجيل.
    </div>
</div>

<!-- Step Error Message -->
<div x-show="stepError" x-transition
     class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mt-4 text-sm">
    <svg class="w-5 h-5 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
    </svg>
    <span x-text="stepError"></span>
</div>

<!-- Navigation Buttons -->
<div class="flex items-center justify-between mt-4">
    <button type="button" @click="prevStep()" x-show="currentStep > 1"
            class="flex items-center gap-2 px-5 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        السابق
    </button>
    <div x-show="currentStep === 1"></div>

    <div class="flex gap-3">
        <button type="button" @click="nextStep()" x-show="currentStep < 5"
                class="flex items-center gap-2 px-6 py-2.5 bg-primary-800 text-white rounded-lg text-sm hover:bg-primary-700 transition">
            التالي
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>

        <button type="submit" x-show="currentStep === 5"
                :disabled="submitting"
                class="flex items-center gap-2 px-6 py-2.5 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
            <template x-if="!submitting">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </template>
            <template x-if="submitting">
                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            </template>
            <span x-text="submitting ? 'جارٍ الحفظ...' : 'تأكيد تسجيل الدخول'"></span>
        </button>
    </div>
</div>
</form>
</div>
@endsection

@push('scripts')
@if(session('success'))
<script>sessionStorage.removeItem('hotel_checkin_wizard');</script>
@endif
<script>
const CHECKIN_SESSION_KEY = 'hotel_checkin_wizard';
const HAS_BACKEND_ERRORS = {{ $errors->any() ? 'true' : 'false' }};
const BOOKING_MODE = '{{ $mode }}';

function checkInWizard() {
    return {
        currentStep: 1,
        stepLabels: ['بيانات النزيل', 'المرافقون', 'الغرفة', 'التواريخ والدفع', 'التأكيد'],
        guestData: { full_name:'', nationality:'', occupation:'', origin:'', purpose:'', id_type:'national_id', id_number:'', id_issuer:'', id_issue_date:'', phone:'', notes:'' },
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
        currency: 'YER',
        paymentStatus: 'paid',
        paymentMethod: 'cash',
        paidAmount: 0,
        idImagePreview: null,
        idImageName: '',
        floorFilter: 'all',
        typeFilter: 'all',
        idTypeLabel: { national_id:'هوية وطنية', passport:'جواز سفر', residence:'إقامة' },
        submitting: false,
        stepError: '',

        init() {
            const today = new Date().toISOString().split('T')[0];
            this.checkInDate = today;
            const tomorrow = new Date(); tomorrow.setDate(tomorrow.getDate()+1);
            this.checkOutDate = tomorrow.toISOString().split('T')[0];
            if (BOOKING_MODE === 'checkin') {
                const now = new Date();
                this.checkInTime = now.toTimeString().slice(0, 5);
            }

            // Only restore saved state when recovering from a backend validation error
            if (HAS_BACKEND_ERRORS) {
                this.restoreFromSession();
                if (this.roomId) this.currentStep = 5;
            } else {
                // Fresh visit — clear any leftover session data from a previous incomplete form
                sessionStorage.removeItem(CHECKIN_SESSION_KEY);
            }

            // Auto-clear step error when user fills in data
            this.$watch('guestData', () => { if (this.stepError) this.stepError = ''; }, { deep: true });
            this.$watch('roomId',     () => { if (this.stepError) this.stepError = ''; });
            this.$watch('checkInDate',  () => { if (this.stepError) this.stepError = ''; });
            this.$watch('checkOutDate', () => { if (this.stepError) this.stepError = ''; });
            this.$watch('idImagePreview', () => { if (this.stepError) this.stepError = ''; });
        },

        saveToSession() {
            try {
                sessionStorage.setItem(CHECKIN_SESSION_KEY, JSON.stringify({
                    currentStep:     this.currentStep,
                    guestData:       this.guestData,
                    companions:      this.companions.map(c => ({ ...c, id_preview: null })),
                    roomId:          this.roomId,
                    selectedRoom:    this.selectedRoom,
                    linkedInfo:      this.linkedInfo,
                    suiteBookingType:this.suiteBookingType,
                    checkInDate:     this.checkInDate,
                    checkInTime:     this.checkInTime,
                    checkOutDate:    this.checkOutDate,
                    nightsInput:     this.nightsInput,
                    paymentStatus:   this.paymentStatus,
                    paymentMethod:   this.paymentMethod,
                    paidAmount:      this.paidAmount,
                    customPrice:     this.customPrice,
                }));
            } catch(e) {}
        },

        restoreFromSession() {
            try {
                const raw = sessionStorage.getItem(CHECKIN_SESSION_KEY);
                if (!raw) return;
                const s = JSON.parse(raw);
                this.guestData        = s.guestData        ?? this.guestData;
                this.companions       = s.companions        ?? [];
                this.roomId           = s.roomId            ?? '';
                this.selectedRoom     = s.selectedRoom      ?? null;
                this.linkedInfo       = s.linkedInfo        ?? null;
                this.suiteBookingType = s.suiteBookingType  ?? 'a_only';
                this.checkInDate      = s.checkInDate       || this.checkInDate;
                this.checkInTime      = s.checkInTime       || this.checkInTime;
                this.checkOutDate     = s.checkOutDate      || this.checkOutDate;
                this.nightsInput      = s.nightsInput       ?? 1;
                this.paymentStatus    = s.paymentStatus     ?? 'paid';
                this.paymentMethod    = s.paymentMethod     ?? 'cash';
                this.paidAmount       = s.paidAmount        ?? 0;
                this.customPrice      = s.customPrice       ?? null;
                this.currentStep      = s.currentStep       ?? 1;
                this.$nextTick(() => this.calcTotal());
            } catch(e) {}
        },

        handleSubmit(event) {
            this.submitting = true;
            // Keep session alive — will be cleared server-side on success
        },

        today() {
            return new Date().toISOString().split('T')[0];
        },

        addCompanion() {
            this.companions.push({ full_name:'', nationality:'', id_type:'national_id', id_number:'', id_issuer:'', id_issue_date:'', relationship:'other', id_preview:null });
        },

        removeCompanion(idx) {
            this.companions.splice(idx, 1);
        },

        selectRoom(room, info, forcedType = null) {
            this.selectedRoom = room;
            this.linkedInfo = info || null;
            this.roomId = room.id;
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
            this.linkedInfo = null;
            this.roomId = '';
            this.suiteBookingType = 'a_only';
            this.totalAmount = 0;
            this.nights = 0;
            this.saveToSession();
        },

        // Room's configured YER price (falls back to base_price)
        roomBasePriceFor(cur) {
            if (!this.selectedRoom) return 0;
            const prices = this.selectedRoom.prices || {};
            let val = parseFloat(prices['YER']);
            if (isNaN(val) || val <= 0) {
                val = parseFloat(this.selectedRoom.base_price) || 0;
            }
            return isNaN(val) ? 0 : val;
        },

        effectiveRoomPrice() {
            const base = (this.customPrice !== null && this.customPrice !== '')
                ? parseFloat(this.customPrice) || 0
                : this.roomBasePriceFor('YER');
            return this.suiteBookingType === 'both' ? base * 2 : base;
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
                this.nights = Math.max(0, Math.floor((d2 - d1) / 86400000));
                this.nightsInput = this.nights || 1;
                this.totalAmount = this.nights * this.effectiveRoomPrice();
                this.saveToSession();
            }
        },

        get effectivePaid() {
            if (this.paymentStatus === 'paid') return this.totalAmount;
            if (this.paymentStatus === 'partial') return parseFloat(this.paidAmount) || 0;
            return 0;
        },

        getStepError() {
            if (this.currentStep === 1) {
                if (!this.guestData.full_name)   return 'الاسم الرباعي مطلوب';
                if (!this.guestData.id_number)   return 'رقم الهوية مطلوب';
                if (!this.guestData.phone)        return 'رقم الجوال مطلوب';
                if (!this.idImagePreview && !this.idImageName) return 'صورة الهوية مطلوبة';
                return '';
            }
            if (this.currentStep === 2) {
                for (let i = 0; i < this.companions.length; i++) {
                    const c = this.companions[i];
                    const n = i + 1;
                    if (!c.full_name || !c.full_name.trim())   return `مرافق ${n}: الاسم مطلوب`;
                    if (!c.nationality || !c.nationality.trim()) return `مرافق ${n}: الجنسية مطلوبة`;
                    if (!c.relationship)                        return `مرافق ${n}: صلة القرابة مطلوبة`;
                    if (!c.id_preview)                          return `مرافق ${n}: صورة الهوية مطلوبة`;
                }
                return '';
            }
            if (this.currentStep === 3) {
                if (!this.roomId) return 'يرجى اختيار غرفة';
                return '';
            }
            if (this.currentStep === 4) {
                if (!this.checkInDate)              return 'تاريخ الدخول مطلوب';
                if (!this.checkOutDate)             return 'تاريخ الخروج مطلوب';
                if (this.nights <= 0)               return 'تاريخ الخروج يجب أن يكون بعد تاريخ الدخول';
                if (this.effectiveRoomPrice() <= 0) return 'لا يوجد سعر للغرفة بالعملة المختارة — أدخل السعر يدوياً';
                if (this.paymentStatus === 'partial' && (parseFloat(this.paidAmount) || 0) <= 0) return 'يرجى إدخال المبلغ المدفوع';
                if (this.paymentStatus === 'partial' && (parseFloat(this.paidAmount) || 0) > this.totalAmount) return `المبلغ المدفوع يتجاوز إجمالي الحجز (${this.totalAmount.toLocaleString('ar-SA')} ر.ي)`;
                return '';
            }
            return '';
        },

        canProceed() {
            return this.getStepError() === '';
        },

        nextStep() {
            const err = this.getStepError();
            if (err) {
                this.stepError = err;
                return;
            }
            this.stepError = '';
            if (this.currentStep < 5) {
                this.currentStep++;
                this.saveToSession();
            }
        },
        prevStep() {
            if (this.currentStep > 1) {
                this.stepError = '';
                this.currentStep--;
                this.saveToSession();
            }
        },

        formatNumber(n) {
            return (parseFloat(n) || 0).toLocaleString('ar-YE');
        },

        handleIdImageChange(e) {
            const file = e.target.files[0];
            if (file) {
                this.idImageName = file.name;
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (ev) => this.idImagePreview = ev.target.result;
                    reader.readAsDataURL(file);
                } else {
                    this.idImagePreview = null;
                    this.idImageName = file.name + ' (PDF)';
                }
            }
        },

        handleIdImageDrop(e) {
            const file = e.dataTransfer.files[0];
            if (file) {
                const input = document.querySelector('input[name="id_image"]');
                if (input) {
                    const dt = new DataTransfer(); dt.items.add(file); input.files = dt.files;
                }
                this.handleIdImageChange({ target: { files: [file] } });
            }
        },

        handleCompanionIdImage(e, idx) {
            const file = e.target.files[0];
            if (!file) return;
            this.companions[idx].id_file_selected = true;
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (ev) => this.companions[idx].id_preview = ev.target.result;
                reader.readAsDataURL(file);
            } else {
                // PDF — no preview but mark as selected
                this.companions[idx].id_preview = '__pdf__';
            }
        },
    }
}

</script>
@endpush
