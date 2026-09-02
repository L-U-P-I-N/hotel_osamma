@extends('layouts.app')
@section('title', 'تعديل الحجز #' . $reservation->id)
@section('page-title', 'تعديل الحجز #' . $reservation->id)

@section('content')
<div class="max-w-2xl mx-auto">
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
        <a href="{{ route('reservations.show', $reservation) }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <h2 class="text-lg font-semibold text-gray-800">تعديل بيانات الحجز</h2>
    </div>

    <!-- Guest Info (read-only) -->
    <div class="mb-5 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <div class="flex items-center justify-between flex-wrap gap-3 text-sm">
            <div>
                <span class="text-gray-500">النزيل:</span>
                <span class="font-semibold text-gray-800 mr-2">{{ $reservation->guest->full_name }}</span>
            </div>
            <div>
                <span class="text-gray-500">الغرفة الحالية:</span>
                <span class="font-semibold mr-2" style="color:#0F4C75;">{{ $reservation->room->room_number }}</span>
                <span class="text-gray-400 text-xs">({{ $reservation->room->roomType->name }})</span>
            </div>
        </div>
    </div>

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

    <form method="POST" action="{{ route('reservations.update', $reservation) }}" class="space-y-5"
          x-data="{
            checkIn: '{{ old('check_in_date', $reservation->check_in_date->format('Y-m-d')) }}',
            checkOut: '{{ old('check_out_date', $reservation->check_out_date->format('Y-m-d')) }}',
            unitPrice: {{ old('nightly_price', $unitPrice) }},
            multiplier: {{ $multiplier }},
            minPrice: {{ $roomType->effective_min_price }},
            maxPrice: {{ $roomType->effective_max_price }},
            get pricePerNight() { return (parseFloat(this.unitPrice) || 0) * this.multiplier; },
            get priceOutOfRange() {
                const p = parseFloat(this.unitPrice);
                return isNaN(p) || p < this.minPrice || p > this.maxPrice;
            },
            get nights() {
                if (!this.checkIn || !this.checkOut) return 0;
                const d = (new Date(this.checkOut) - new Date(this.checkIn)) / 86400000;
                return d > 0 ? d : 0;
            },
            get total() { return this.nights * this.pricePerNight; }
          }">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">تاريخ الدخول <span class="text-red-500">*</span></label>
                <input type="date" name="check_in_date" x-model="checkIn"
                       value="{{ old('check_in_date', $reservation->check_in_date->format('Y-m-d')) }}" required
                       class="w-full border @error('check_in_date') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                @error('check_in_date')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">تاريخ الخروج <span class="text-red-500">*</span></label>
                <input type="date" name="check_out_date" x-model="checkOut"
                       value="{{ old('check_out_date', $reservation->check_out_date->format('Y-m-d')) }}" required
                       class="w-full border @error('check_out_date') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                @error('check_out_date')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Nightly price — محكوم بنطاق يحدده المدير -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    سعر الليلة للوحدة ({{ $roomType->name }})
                    @if($multiplier > 1)<span class="text-xs text-gray-400">— يُضرب × {{ $multiplier }} لأن الحجز يشمل وحدتين</span>@endif
                </label>
                <input type="number" name="nightly_price" x-model.number="unitPrice" step="0.01"
                       min="{{ $roomType->effective_min_price }}" max="{{ $roomType->effective_max_price }}"
                       @readonly(!$canEditPrice) @disabled(!$canEditPrice)
                       class="w-full border @error('nightly_price') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition {{ $canEditPrice ? '' : 'bg-gray-100 text-gray-500 cursor-not-allowed' }}">
                @if($canEditPrice)
                <p class="mt-1 text-xs text-gray-500">
                    النطاق المسموح: {{ number_format($roomType->effective_min_price, 0) }} —
                    {{ number_format($roomType->effective_max_price, 0) }} ر.ي
                </p>
                <p x-show="priceOutOfRange" class="mt-1 text-xs text-red-600">السعر خارج النطاق المسموح وسيُرفض عند الحفظ.</p>
                @else
                <p class="mt-1 text-xs text-gray-500">السعر ثابت حسب إعدادات المدير — لا تملك صلاحية تعديله.</p>
                @endif
                @error('nightly_price')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Live calculation -->
            <div class="md:col-span-2 p-4 rounded-lg border" style="background:#f0f7ff; border-color:#c7dff7;">
                <div class="flex items-center justify-between text-sm flex-wrap gap-2">
                    <div class="flex items-center gap-4">
                        <span class="text-gray-600">عدد الليالي: <strong x-text="nights" class="text-gray-900"></strong></span>
                        <span class="text-gray-600">السعر/ليلة: <strong x-text="pricePerNight.toLocaleString('ar-SA')"></strong> ر.ي</span>
                    </div>
                    <div class="text-base font-bold" style="color:#0F4C75;">
                        الإجمالي: <span x-text="total.toLocaleString('ar-SA')"></span> ر.ي
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">الغرض من الزيارة</label>
                <input type="text" name="purpose" value="{{ old('purpose', $reservation->purpose) }}" maxlength="255"
                       class="w-full border @error('purpose') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                @error('purpose')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">جهة القدوم</label>
                <input type="text" name="origin" value="{{ old('origin', $reservation->origin) }}" maxlength="255"
                       class="w-full border @error('origin') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                @error('origin')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">ملاحظات</label>
                <textarea name="notes" rows="3" maxlength="1000"
                          class="w-full border @error('notes') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition resize-none">{{ old('notes', $reservation->notes) }}</textarea>
                @error('notes')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-xs text-yellow-800">
            ملاحظة: يُعاد حساب الإجمالي في الخادم من التواريخ وسعر الليلة المعتمد،
            ولا يُقبل أي سعر خارج النطاق الذي حدده المدير.
            @if((float)$reservation->discount_amount > 0)
            الخصم المسجّل ({{ number_format($reservation->discount_amount, 2) }} ر.ي) يبقى مطبقاً على الإجمالي الجديد.
            @endif
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex items-center gap-2 px-6 py-2.5 text-white rounded-lg text-sm font-medium transition" style="background:#0F4C75;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                حفظ التغييرات
            </button>
            <a href="{{ route('reservations.show', $reservation) }}" class="px-6 py-2.5 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition">إلغاء</a>
        </div>
    </form>
</div>
</div>
@endsection
