@extends('layouts.app')
@section('title', 'تعديل الحجز #' . $reservation->id)
@section('page-title', 'تعديل الحجز #' . $reservation->id)
@section('back-url', route('reservations.show', $reservation))

@section('content')
<div class="max-w-3xl mx-auto" x-data="editReservation()">

@if(session('error'))
<div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-lg">
    <p class="text-sm font-semibold text-red-700">{{ session('error') }}</p>
</div>
@endif

@if($errors->any())
<div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-lg">
    <p class="text-sm font-semibold text-red-700 mb-2">يرجى تصحيح الأخطاء التالية:</p>
    <ul class="list-disc list-inside space-y-1">
        @foreach($errors->all() as $error)
        <li class="text-sm text-red-600">{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif


<form method="POST" action="{{ route('reservations.update', $reservation) }}" enctype="multipart/form-data" class="space-y-5">
    @csrf
    @method('PUT')

    <!-- Guest Data -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            بيانات النزيل
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">الاسم الكامل <span class="text-red-500">*</span></label>
                <input type="text" name="guest_full_name" required maxlength="255"
                       value="{{ old('guest_full_name', $reservation->guest?->full_name) }}"
                       class="w-full border @error('guest_full_name') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">الجنسية</label>
                <input type="text" name="guest_nationality" maxlength="100"
                       value="{{ old('guest_nationality', $reservation->guest?->nationality) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">المهنة</label>
                <input type="text" name="guest_occupation" maxlength="100"
                       value="{{ old('guest_occupation', $reservation->guest?->occupation) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">نوع الهوية</label>
                <select name="guest_id_type" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                    <option value="">-- اختر --</option>
                    <option value="national_id" {{ old('guest_id_type', $reservation->guest?->id_type) === 'national_id' ? 'selected' : '' }}>بطاقة هوية</option>
                    <option value="passport" {{ old('guest_id_type', $reservation->guest?->id_type) === 'passport' ? 'selected' : '' }}>جواز سفر</option>
                    <option value="residence" {{ old('guest_id_type', $reservation->guest?->id_type) === 'residence' ? 'selected' : '' }}>إقامة</option>
                    <option value="other" {{ old('guest_id_type', $reservation->guest?->id_type) === 'other' ? 'selected' : '' }}>أخرى</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">رقم الهوية</label>
                <input type="text" name="guest_id_number" maxlength="50"
                       value="{{ old('guest_id_number', $reservation->guest?->id_number) }}"
                       class="w-full border @error('guest_id_number') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none" dir="ltr">
                @error('guest_id_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">جهة الإصدار</label>
                <input type="text" name="guest_id_issuer" maxlength="100"
                       value="{{ old('guest_id_issuer', $reservation->guest?->id_issuer) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">تاريخ الإصدار</label>
                <input type="date" name="guest_id_issue_date"
                       value="{{ old('guest_id_issue_date', $reservation->guest?->id_issue_date?->format('Y-m-d')) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">رقم الهاتف</label>
                <input type="text" name="guest_phone" maxlength="30"
                       value="{{ old('guest_phone', $reservation->guest?->phone) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none" dir="ltr">
            </div>

            {{-- Guest ID image (view current + replace) --}}
            <div class="md:col-span-2" x-data="{ preview: null, isPdf: false }">
                <label class="block text-xs font-medium text-gray-600 mb-1">صورة الهوية</label>
                <div class="flex items-center gap-4 p-3 border border-gray-200 rounded-lg bg-gray-50">
                    {{-- Thumbnail: new preview > current image > empty --}}
                    <div class="shrink-0">
                        <template x-if="preview && !isPdf">
                            <img :src="preview" class="w-24 h-20 object-cover rounded-lg border border-gray-200">
                        </template>
                        <template x-if="preview && isPdf">
                            <div class="w-24 h-20 rounded-lg border border-gray-200 bg-red-50 flex flex-col items-center justify-center text-xs text-red-500 font-medium">
                                <svg class="w-6 h-6 mb-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5z"/></svg>
                                PDF جديد
                            </div>
                        </template>
                        <template x-if="!preview">
                            <div>
                                @if($reservation->guest?->id_image_path)
                                    @php $gExt = strtolower(pathinfo($reservation->guest->id_image_path, PATHINFO_EXTENSION)); @endphp
                                    @if($gExt === 'pdf')
                                    <a href="{{ route('guests.idImage', $reservation->guest) }}" target="_blank"
                                       class="w-24 h-20 rounded-lg border border-gray-200 bg-red-50 flex flex-col items-center justify-center text-xs text-red-500 font-medium">
                                        <svg class="w-6 h-6 mb-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5z"/></svg>
                                        PDF الحالي
                                    </a>
                                    @else
                                    <a href="{{ route('guests.idImage', $reservation->guest) }}" target="_blank">
                                        <img src="{{ route('guests.idImage', $reservation->guest) }}" alt="هوية النزيل"
                                             class="w-24 h-20 object-cover rounded-lg border border-gray-200 hover:opacity-90 transition">
                                    </a>
                                    @endif
                                @else
                                    <div class="w-24 h-20 rounded-lg border-2 border-dashed border-gray-200 bg-white flex items-center justify-center text-xs text-gray-400 text-center px-2">لا توجد صورة</div>
                                @endif
                            </div>
                        </template>
                    </div>
                    {{-- File input --}}
                    <div class="flex-1">
                        <input type="file" name="guest_id_image" accept="image/*,.pdf"
                               @change="const f=$event.target.files[0]; if(!f){preview=null;return;} isPdf=!f.type.startsWith('image/'); if(isPdf){preview='pdf';}else{const r=new FileReader();r.onload=e=>preview=e.target.result;r.readAsDataURL(f);}"
                               class="w-full text-sm text-gray-600 file:ml-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 cursor-pointer">
                        <p class="text-xs text-gray-400 mt-1.5">JPG · PNG · PDF — حتى 5MB. اتركه فارغاً للإبقاء على الصورة الحالية.</p>
                        @error('guest_id_image')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Companions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-700 flex items-center gap-2">
                <svg class="w-5 h-5 text-primary-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                المرافقون
                <span x-show="companions.filter(c=>!c.delete).length > 0"
                      class="px-2 py-0.5 bg-violet-100 text-violet-700 text-xs font-semibold rounded-full"
                      x-text="companions.filter(c=>!c.delete).length"></span>
            </h3>
            <button type="button" @click="addCompanion()"
                    class="flex items-center gap-1.5 text-sm px-3 py-1.5 bg-primary-50 text-primary-800 rounded-lg hover:bg-primary-100 transition font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                إضافة مرافق
            </button>
        </div>

        <div class="space-y-4">
            <template x-for="(comp, idx) in companions" :key="comp._key">
                <div class="border rounded-xl p-4 relative transition"
                     :class="comp.delete ? 'opacity-50 bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200'">

                    <input type="hidden" :name="`companions[${idx}][id]`" :value="comp.id ?? ''">
                    <input type="hidden" :name="`companions[${idx}][delete]`" :value="comp.delete ? '1' : '0'">

                    {{-- Header row --}}
                    <div class="flex items-center justify-between mb-3">
                        <span class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-violet-100 text-violet-700 text-xs font-bold flex items-center justify-center"
                                  x-text="idx + 1"></span>
                            <span class="text-sm font-medium text-gray-700"
                                  x-text="comp.full_name || 'مرافق جديد'"></span>
                            <span x-show="!comp.id" class="px-1.5 py-0.5 bg-green-100 text-green-700 text-xs rounded">جديد</span>
                        </span>
                        <button type="button" @click="toggleDelete(idx)"
                                class="text-xs px-2.5 py-1 rounded-lg transition font-medium"
                                :class="comp.delete ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200'">
                            <span x-text="comp.delete ? 'استعادة' : 'حذف'"></span>
                        </button>
                    </div>

                    <div :class="comp.delete ? 'pointer-events-none' : ''">
                        {{-- Row 1: name, nationality, relationship --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">الاسم الكامل <span class="text-red-500">*</span></label>
                                <input type="text" :name="`companions[${idx}][full_name]`" x-model="comp.full_name"
                                       :required="!comp.delete" :disabled="comp.delete"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none bg-white">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">الجنسية</label>
                                <input type="text" :name="`companions[${idx}][nationality]`" x-model="comp.nationality"
                                       :disabled="comp.delete"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none bg-white">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">صلة القرابة <span class="text-red-500">*</span></label>
                                <select :name="`companions[${idx}][relationship]`" x-model="comp.relationship"
                                        :disabled="comp.delete"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none bg-white">
                                    <option value="">-- اختر --</option>
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
                        </div>

                        {{-- Row 2: id type, id number, issuer, issue_date --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">نوع الهوية</label>
                                <select :name="`companions[${idx}][id_type]`" x-model="comp.id_type"
                                        :disabled="comp.delete"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none bg-white">
                                    <option value="">-- اختر --</option>
                                    <option value="national_id">هوية وطنية</option>
                                    <option value="passport">جواز سفر</option>
                                    <option value="residence">إقامة</option>
                                    <option value="other">أخرى</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">رقم الهوية</label>
                                <input type="text" :name="`companions[${idx}][id_number]`" x-model="comp.id_number"
                                       :disabled="comp.delete"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none bg-white" dir="ltr">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">جهة الإصدار</label>
                                <input type="text" :name="`companions[${idx}][id_issuer]`" x-model="comp.id_issuer"
                                       :disabled="comp.delete"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none bg-white">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">تاريخ الإصدار</label>
                                <input type="date" :name="`companions[${idx}][id_issue_date]`" x-model="comp.id_issue_date"
                                       :disabled="comp.delete"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none bg-white">
                            </div>
                        </div>

                        {{-- Row 3: file uploads --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            {{-- ID Image --}}
                            <div class="border border-dashed border-gray-300 rounded-xl p-3 bg-white">
                                <p class="text-xs text-gray-500 mb-2 font-medium">صورة الهوية</p>
                                <template x-if="comp.has_image && !comp.new_id_image">
                                    <p class="text-xs text-green-600 mb-1.5 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                                        صورة محفوظة — اختر جديدة للاستبدال
                                    </p>
                                </template>
                                <template x-if="comp.new_id_image">
                                    <div class="mb-1.5">
                                        <template x-if="comp.new_id_image !== '__pdf__'">
                                            <img :src="comp.new_id_image" class="h-16 rounded-lg object-cover border border-gray-200">
                                        </template>
                                        <template x-if="comp.new_id_image === '__pdf__'">
                                            <span class="text-xs text-red-600 font-medium">📄 ملف PDF محدد</span>
                                        </template>
                                    </div>
                                </template>
                                <input type="file" :name="`companions[${idx}][id_image]`"
                                       accept="image/*,.pdf" :disabled="comp.delete"
                                       @change="handleIdImage($event, idx)"
                                       class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:bg-primary-50 file:text-primary-700 file:font-medium hover:file:bg-primary-100">
                            </div>

                            {{-- Marriage Doc (only for wife) --}}
                            <div x-show="comp.relationship === 'wife'"
                                 class="border border-dashed border-pink-200 rounded-xl p-3 bg-pink-50">
                                <p class="text-xs text-pink-700 mb-2 font-medium">وثيقة الزواج</p>
                                <template x-if="comp.has_marriage && !comp.new_marriage_doc">
                                    <p class="text-xs text-green-600 mb-1.5 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                                        وثيقة محفوظة — اختر جديدة للاستبدال
                                    </p>
                                </template>
                                <template x-if="comp.new_marriage_doc">
                                    <div class="mb-1.5">
                                        <template x-if="comp.new_marriage_doc !== '__pdf__'">
                                            <img :src="comp.new_marriage_doc" class="h-16 rounded-lg object-cover border border-pink-200">
                                        </template>
                                        <template x-if="comp.new_marriage_doc === '__pdf__'">
                                            <span class="text-xs text-red-600 font-medium">📄 ملف PDF محدد</span>
                                        </template>
                                    </div>
                                </template>
                                <input type="file" :name="`companions[${idx}][marriage_doc]`"
                                       accept="image/*,.pdf" :disabled="comp.delete"
                                       @change="handleMarriageDoc($event, idx)"
                                       class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:bg-pink-100 file:text-pink-700 file:font-medium hover:file:bg-pink-200">
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <div x-show="companions.length === 0"
                 class="text-center py-10 text-gray-400 text-sm border-2 border-dashed border-gray-200 rounded-xl">
                <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                لا يوجد مرافقون — اضغط "إضافة مرافق" لإضافة مرافق جديد
            </div>
        </div>
    </div>

    <!-- Booking Details -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            تفاصيل الحجز
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">تاريخ الدخول <span class="text-red-500">*</span></label>
                <input type="date" name="check_in_date" x-model="checkIn" required
                       class="w-full border @error('check_in_date') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                @error('check_in_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">تاريخ الخروج <span class="text-red-500">*</span></label>
                <input type="date" name="check_out_date" x-model="checkOut" required
                       class="w-full border @error('check_out_date') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                @error('check_out_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2 p-3 rounded-lg bg-blue-50 border border-blue-100 text-sm">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <span class="text-gray-600">عدد الليالي: <strong x-text="nights" class="text-gray-900"></strong></span>
                    <span class="font-bold text-primary-800">
                        الإجمالي المحسوب: <span x-text="total.toLocaleString('ar-SA')"></span> {{ $reservation->currency ?? 'ر.ي' }}
                    </span>
                </div>
            </div>

            <!-- Price override -->
            <div class="md:col-span-2 border border-amber-200 bg-amber-50 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-sm font-medium text-amber-800">
                        تعديل سعر الليلة (تفاوض) — {{ $reservation->currency ?? 'YER' }}
                    </label>
                    <button type="button" x-show="customPrice != basePrice"
                            @click="customPrice = basePrice"
                            class="text-xs text-gray-400 hover:text-red-500 underline">
                        استعادة السعر الأصلي (<span x-text="basePrice.toLocaleString('ar-SA')"></span>)
                    </button>
                </div>
                <div class="flex items-center gap-3">
                    <input type="number" name="price_per_night" min="0" step="100"
                           x-model.number="customPrice"
                           class="flex-1 border border-amber-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 outline-none bg-white">
                    <span class="text-sm text-amber-700 font-medium whitespace-nowrap">{{ $reservation->currency ?? 'ر.ي' }} / ليلة</span>
                </div>
                <p class="text-xs text-amber-600 mt-1.5">اتركه فارغاً لاستخدام السعر الأصلي للغرفة بعملة <span class="font-semibold">{{ $reservation->currency ?? 'YER' }}</span></p>
                @error('price_per_night')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Renewal price -->
            <div class="md:col-span-2 border border-blue-200 bg-blue-50 rounded-xl p-4">
                <label class="text-sm font-medium text-blue-800 mb-2 block">
                    سعر الليلة عند التجديد — {{ $reservation->currency ?? 'YER' }}
                </label>
                <div class="flex items-center gap-3">
                    <input type="number" name="renewal_price_per_night" min="0" step="100"
                           value="{{ old('renewal_price_per_night', $reservation->renewal_price_per_night) }}"
                           placeholder="نفس سعر الليلة الأولى ({{ number_format($reservation->effective_renewal_price_per_night, 0) }})"
                           class="flex-1 border border-blue-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-400 outline-none bg-white">
                    <span class="text-sm text-blue-700 font-medium whitespace-nowrap">{{ $reservation->currency ?? 'ر.ي' }} / ليلة</span>
                </div>
                <p class="text-xs text-blue-600 mt-1.5">اتركه فارغاً إذا كان سعر التجديد نفس سعر الليلة الأولى. استخدمه فقط إذا اتُّفق مع النزيل على سعر خاص لليوم الأول يختلف عن سعر أي تجديد لاحق.</p>
                @error('renewal_price_per_night')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">الغرض من الزيارة</label>
                <input type="text" name="purpose" maxlength="255"
                       value="{{ old('purpose', $reservation->purpose) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">جهة القدوم</label>
                <input type="text" name="origin" maxlength="255"
                       value="{{ old('origin', $reservation->origin) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">ملاحظات</label>
                <textarea name="notes" rows="3" maxlength="1000"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none resize-none">{{ old('notes', $reservation->notes) }}</textarea>
            </div>
        </div>
    </div>

    <!-- Submit -->
    <div class="flex gap-3">
        <button type="submit" class="flex items-center gap-2 px-6 py-2.5 text-white rounded-lg text-sm font-medium transition" style="background:#0F4C75;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            حفظ التغييرات
        </button>
        <a href="{{ route('reservations.show', $reservation) }}" class="px-6 py-2.5 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition">إلغاء</a>
    </div>
</form>
</div>
@endsection

@push('scripts')
<script>
function editReservation() {
    return {
        checkIn: '{{ old('check_in_date', $reservation->check_in_date?->format('Y-m-d') ?? '') }}',
        checkOut: '{{ old('check_out_date', $reservation->check_out_date?->format('Y-m-d') ?? '') }}',
        basePrice: {{ $reservation->room?->roomType?->base_price ?? 0 }},
        customPrice: {{ old('price_per_night', $currentPricePerNight) }},
        companions: @json($companionsData),
        _nextKey: {{ $reservation->companions->count() + 1 }},
        // أوقات الوصول/الخروج المحفوظة (لا تُعدَّل من هذا النموذج) — تُستخدم لحساب الأيام
        checkInTime: '{{ \Illuminate\Support\Str::substr($reservation->check_in_time ?? '', 0, 5) ?: '00:00' }}',
        checkOutTime: '{{ \Illuminate\Support\Str::substr($reservation->check_out_time ?? '', 0, 5) ?: '13:00' }}',

        get nights() {
            // عدد الأيام = عدد مرّات مرور حد 1 ظهراً حصراً بين لحظتي الوصول والخروج + 1
            if (!this.checkIn || !this.checkOut) return 0;
            const ci = new Date(this.checkIn + 'T' + this.checkInTime);
            const co = new Date(this.checkOut + 'T' + this.checkOutTime);
            if (isNaN(ci) || isNaN(co) || co <= ci) return 1;
            let b = new Date(this.checkIn + 'T13:00');
            if (b <= ci) b.setDate(b.getDate() + 1);
            let crossings = 0;
            while (b < co) { crossings++; b.setDate(b.getDate() + 1); }
            return Math.max(1, crossings + 1);
        },
        get total() { return this.nights * (this.customPrice > 0 ? this.customPrice : this.basePrice); },

        addCompanion() {
            this.companions.push({
                id: null,
                full_name: '',
                nationality: '',
                id_type: '',
                id_number: '',
                id_issuer: '',
                id_issue_date: '',
                relationship: '',
                has_image: false,
                has_marriage: false,
                new_id_image: null,
                new_marriage_doc: null,
                delete: false,
                _key: 'new_' + (this._nextKey++),
            });
        },
        toggleDelete(idx) {
            if (this.companions[idx].id === null) {
                this.companions.splice(idx, 1);
            } else {
                this.companions[idx].delete = !this.companions[idx].delete;
            }
        },
        handleIdImage(e, idx) {
            const file = e.target.files[0];
            if (!file) return;
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (ev) => { this.companions[idx].new_id_image = ev.target.result; };
                reader.readAsDataURL(file);
            } else {
                this.companions[idx].new_id_image = '__pdf__';
            }
        },
        handleMarriageDoc(e, idx) {
            const file = e.target.files[0];
            if (!file) return;
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (ev) => { this.companions[idx].new_marriage_doc = ev.target.result; };
                reader.readAsDataURL(file);
            } else {
                this.companions[idx].new_marriage_doc = '__pdf__';
            }
        },
    }
}
</script>
@endpush
