@extends('layouts.app')
@section('title', 'تعديل الغرفة ' . $room->room_number)
@section('page-title', 'تعديل الغرفة ' . $room->room_number)

@section('content')
@php
    $statusColors = [
        'available'       => 'bg-green-100 text-green-800',
        'reserved'        => 'bg-blue-100 text-blue-800',
        'occupied'        => 'bg-red-100 text-red-800',
        'under_inspection'=> 'bg-yellow-100 text-yellow-800',
        'maintenance'     => 'bg-gray-100 text-gray-800',
    ];
    $statusLabels = [
        'available'        => 'متاحة',
        'reserved'         => 'محجوزة',
        'occupied'         => 'مشغولة',
        'under_inspection' => 'تحت الفحص',
        'maintenance'      => 'صيانة',
    ];
@endphp

<div class="max-w-2xl mx-auto space-y-5">

    {{-- ───── بيانات الغرفة ───── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
            <a href="{{ route('rooms.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">تعديل بيانات الغرفة</h2>
            <span class="px-2 py-0.5 text-xs font-medium rounded-full" style="background:#e8f0f7;color:#0F4C75;">{{ $room->room_number }}</span>
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

        <form method="POST" action="{{ route('rooms.update', $room) }}" class="space-y-5" x-data="floorSelector({{ $floors->toJson() }}, '{{ old('floor', $room->floor) }}', '{{ old('room_number', $room->room_number) }}')">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">رقم الغرفة <span class="text-red-500">*</span></label>
                    <input type="text" name="room_number" value="{{ old('room_number', $room->room_number) }}" required
                           class="w-full border @error('room_number') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                    @error('room_number')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">رقم الطابق <span class="text-red-500">*</span></label>
                    <input type="number" name="floor" value="{{ old('floor', $room->floor) }}" required min="1" max="30"
                           class="w-full border @error('floor') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                    @error('floor')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">الفئة <span class="text-red-500">*</span></label>
                    <select name="room_type_id" required
                            class="w-full border @error('room_type_id') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                        @foreach($roomTypes as $type)
                        <option value="{{ $type->id }}" {{ old('room_type_id', $room->room_type_id) == $type->id ? 'selected' : '' }}>
                            {{ $type->name }} — {{ number_format($type->base_price, 0) }} ر.ي / ليلة
                        </option>
                        @endforeach
                    </select>
                    @error('room_type_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">نوع الغرفة <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        @foreach([
                            'regular'   => ['label' => 'عادية',   'icon' => '🛏️'],
                            'double'    => ['label' => 'زوجية',   'icon' => '👫'],
                            'suite_a'   => ['label' => 'جناح A',  'icon' => '🏨'],
                            'suite_b'   => ['label' => 'جناح B',  'icon' => '🏨'],
                            'hall'      => ['label' => 'صالة',    'icon' => '🏛️'],
                            'apartment' => ['label' => 'شقة',     'icon' => '🏠'],
                        ] as $value => $opt)
                        <label class="cursor-pointer">
                            <input type="radio" name="room_sub_type" value="{{ $value }}"
                                   {{ old('room_sub_type', $room->room_sub_type ?? 'regular') === $value ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="border-2 border-gray-200 peer-checked:border-primary-600 peer-checked:bg-primary-50 rounded-xl p-3 text-center transition-all">
                                <div class="text-lg mb-0.5">{{ $opt['icon'] }}</div>
                                <div class="text-xs font-semibold text-gray-700 peer-checked:text-primary-800">{{ $opt['label'] }}</div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('room_sub_type')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">عدد الأسرة <span class="text-red-500">*</span></label>
                    <input type="number" name="beds_count" value="{{ old('beds_count', $room->beds_count ?? 1) }}" min="1" max="20" required
                           class="w-full border @error('beds_count') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                    @error('beds_count')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                @if($canPrice)
                <div class="md:col-span-2">
                    <div class="border border-amber-200 bg-amber-50 rounded-xl p-4">
                        <label class="block text-sm font-semibold text-amber-800 mb-1">سعر الغرفة (ر.ي)</label>
                        <div class="mt-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">سعر الليلة بالريال اليمني</label>
                            <input type="number" name="price_yer" value="{{ old('price_yer', $room->price_yer) }}" min="0" step="0.01"
                                   placeholder="0.00"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none bg-white">
                        </div>
                    </div>
                </div>
                @endif

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">ملاحظات</label>
                    <textarea name="notes" rows="3" maxlength="500"
                              class="w-full border @error('notes') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition resize-none">{{ old('notes', $room->notes) }}</textarea>
                    @error('notes')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex items-center gap-2 px-6 py-2.5 text-white rounded-lg text-sm font-medium transition" style="background:#0F4C75;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    حفظ التغييرات
                </button>
                <a href="{{ route('rooms.index') }}" class="px-6 py-2.5 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition">إلغاء</a>
            </div>
        </form>
    </div>

    {{-- ───── تغيير الحالة ───── --}}
    @canany(['rooms.edit', 'rooms.maintenance'])
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-base font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-100">تغيير حالة الغرفة</h3>

        <div class="flex items-center gap-2 mb-4">
            <span class="text-sm text-gray-500">الحالة الحالية:</span>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$room->status] ?? 'bg-gray-100 text-gray-800' }}">
                {{ $statusLabels[$room->status] ?? $room->status }}
            </span>
        </div>

        <form method="POST" action="{{ route('rooms.updateStatus', $room) }}" class="flex gap-3 items-end">
            @csrf
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">الحالة الجديدة</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 outline-none transition">
                    <option value="available"        {{ $room->status === 'available'        ? 'selected' : '' }}>متاحة</option>
                    <option value="under_inspection" {{ $room->status === 'under_inspection' ? 'selected' : '' }}>تحت الفحص</option>
                    <option value="maintenance"      {{ $room->status === 'maintenance'      ? 'selected' : '' }}>صيانة</option>
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 text-white rounded-lg text-sm font-medium transition" style="background:#0F4C75;">
                تحديث الحالة
            </button>
        </form>
    </div>
    @endcanany

</div>
@push('scripts')
<script>
function floorSelector(floors, oldFloor, oldRoom) {
    return {
        floors: floors,
        selectedFloor: oldFloor ? String(oldFloor) : '',
        selectedRoomNumber: oldRoom || '',
        availableNumbers: [],
        init() {
            if (this.selectedFloor) {
                this.loadRoomNumbers();
            }
        },
        loadRoomNumbers() {
            if (!this.selectedFloor) {
                this.availableNumbers = [];
                return;
            }
            var floor = this.floors.find(f => String(f.floor_number) === String(this.selectedFloor));
            if (!floor) {
                this.availableNumbers = [];
                return;
            }
            fetch('/floors/' + floor.id + '/rooms', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                var current = this.selectedRoomNumber;
                if (current && !data.includes(current)) {
                    data.unshift(current);
                }
                this.availableNumbers = data;
            });
        }
    };
}
(function() {
    var map = {'٠':'0','١':'1','٢':'2','٣':'3','٤':'4','٥':'5','٦':'6','٧':'7','٨':'8','٩':'9',
               '۰':'0','۱':'1','۲':'2','۳':'3','۴':'4','۵':'5','۶':'6','۷':'7','۸':'8','۹':'9'};
    function normalize(v) { return v.replace(/[٠-٩۰-۹]/g, function(d){ return map[d]||d; }); }
    document.querySelectorAll('input[name="room_number"]').forEach(function(el) {
        el.addEventListener('input', function(){ this.value = normalize(this.value); });
    });
})();
</script>
@endpush
@endsection
