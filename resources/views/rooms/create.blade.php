@extends('layouts.app')
@section('title', 'إضافة غرفة جديدة')
@section('page-title', 'إضافة غرفة جديدة')

@section('content')
<div class="max-w-2xl mx-auto">
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
        <a href="{{ route('rooms.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <h2 class="text-lg font-semibold text-gray-800">بيانات الغرفة الجديدة</h2>
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

    <form method="POST" action="{{ route('rooms.store') }}" class="space-y-5" x-data="floorSelector({{ $floors->toJson() }}, '{{ old('floor') }}', '{{ old('room_number') }}')">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">رقم الغرفة <span class="text-red-500">*</span></label>
                <input type="text" name="room_number" value="{{ old('room_number') }}" required
                       placeholder="مثال: 101"
                       class="w-full border @error('room_number') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition">
                @error('room_number')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">رقم الطابق <span class="text-red-500">*</span></label>
                <input type="number" name="floor" value="{{ old('floor', 1) }}" required min="1" max="30"
                       class="w-full border @error('floor') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition">
                @error('floor')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">نوع الغرفة <span class="text-red-500">*</span></label>
                <select name="room_type_id" required
                        class="w-full border @error('room_type_id') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition">
                    <option value="">-- اختر نوع الغرفة --</option>
                    @foreach($roomTypes as $type)
                    <option value="{{ $type->id }}" {{ old('room_type_id') == $type->id ? 'selected' : '' }}>
                        {{ $type->name }} — {{ number_format($type->base_price, 0) }} ر.ي / ليلة
                    </option>
                    @endforeach
                </select>
                @error('room_type_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">تصنيف الغرفة <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    @foreach([
                        'regular'   => ['label' => 'عادية',   'icon' => '🛏️',  'color' => 'gray'],
                        'double'    => ['label' => 'زوجية',   'icon' => '👫',  'color' => 'pink'],
                        'suite_a'   => ['label' => 'جناح A',  'icon' => '🏨',  'color' => 'blue'],
                        'suite_b'   => ['label' => 'جناح B',  'icon' => '🏨',  'color' => 'purple'],
                        'hall'      => ['label' => 'صالة',    'icon' => '🏛️',  'color' => 'yellow'],
                        'apartment' => ['label' => 'شقة',     'icon' => '🏠',  'color' => 'green'],
                    ] as $value => $opt)
                    <label class="cursor-pointer">
                        <input type="radio" name="room_sub_type" value="{{ $value }}"
                               {{ old('room_sub_type', 'regular') === $value ? 'checked' : '' }}
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

            @if($canPrice)
            <div class="md:col-span-2">
                <div class="border border-amber-200 bg-amber-50 rounded-xl p-4">
                    <label class="block text-sm font-semibold text-amber-800 mb-1">أسعار الغرفة حسب العملة</label>
                    <p class="text-xs text-amber-600 mb-3">حدد سعر الليلة لكل عملة بشكل مستقل. اتركه فارغاً إذا لم تكن العملة مستخدمة لهذه الغرفة.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">ريال يمني (YER)</label>
                            <input type="number" name="price_yer" value="{{ old('price_yer') }}" min="0" step="0.01"
                                   placeholder="0.00"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">ريال سعودي (SAR)</label>
                            <input type="number" name="price_sar" value="{{ old('price_sar') }}" min="0" step="0.01"
                                   placeholder="0.00"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">دولار أمريكي (USD)</label>
                            <input type="number" name="price_usd" value="{{ old('price_usd') }}" min="0" step="0.01"
                                   placeholder="0.00"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none bg-white">
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">ملاحظات</label>
                <textarea name="notes" rows="3" maxlength="500"
                          placeholder="أي ملاحظات حول الغرفة..."
                          class="w-full border @error('notes') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition resize-none">{{ old('notes') }}</textarea>
                @error('notes')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex items-center gap-2 px-6 py-2.5 text-white rounded-lg text-sm font-medium transition" style="background:#0F4C75;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                إضافة الغرفة
            </button>
            <a href="{{ route('rooms.index') }}" class="px-6 py-2.5 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition">إلغاء</a>
        </div>
    </form>
</div>
</div>
@push('scripts')
<script>
function floorSelector(floors, oldFloor, oldRoom) {
    return {
        floors: floors,
        selectedFloor: oldFloor || '',
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
            var floor = this.floors.find(f => f.floor_number == this.selectedFloor);
            if (!floor) {
                this.availableNumbers = [];
                return;
            }
            fetch('/floors/' + floor.id + '/rooms', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                this.availableNumbers = data;
                if (this.selectedRoomNumber && !data.includes(this.selectedRoomNumber)) {
                    this.selectedRoomNumber = '';
                }
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
