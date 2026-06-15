@extends('layouts.app')
@section('title', 'الغرف')
@section('page-title', 'إدارة الغرف')

@section('content')
<div x-data="roomsPage()" x-init="init()">

<!-- Filter Bar -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">الحالة</label>
            <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                <option value="">جميع الحالات</option>
                <option value="available" {{ request('status')=='available'?'selected':'' }}>متاحة</option>
                <option value="occupied" {{ request('status')=='occupied'?'selected':'' }}>مشغولة</option>
                <option value="reserved" {{ request('status')=='reserved'?'selected':'' }}>محجوزة</option>
                <option value="under_inspection" {{ request('status')=='under_inspection'?'selected':'' }}>تحت الفحص</option>
                <option value="maintenance" {{ request('status')=='maintenance'?'selected':'' }}>صيانة</option>
            </select>
        </div>
        <div class="flex-1 min-w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">نوع الغرفة</label>
            <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                <option value="">جميع الأنواع</option>
                @foreach($roomTypes as $type)
                <option value="{{ $type->name }}" {{ request('type')==$type->name?'selected':'' }}>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-28">
            <label class="block text-xs font-medium text-gray-600 mb-1">الطابق</label>
            <select name="floor" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                <option value="">جميع الطوابق</option>
                @foreach($floors as $floor)
                <option value="{{ $floor }}" {{ request('floor')==$floor?'selected':'' }}>الطابق {{ $floor }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm hover:bg-primary-700 transition">تصفية</button>
        <a href="{{ route('rooms.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm hover:bg-gray-200 transition">إعادة تعيين</a>
    </form>
</div>

<!-- Status Legend -->
<div class="flex flex-wrap gap-3 mb-4 text-xs">
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-green-500"></span>متاحة</span>
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-500"></span>محجوزة</span>
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500"></span>مشغولة</span>
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-yellow-500"></span>تحت الفحص</span>
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-gray-400"></span>صيانة</span>
</div>

<!-- Rooms Grid -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
    @forelse($rooms as $room)
    @php
        $colors = [
            'available' => 'border-green-300 bg-green-50 hover:bg-green-100',
            'reserved' => 'border-blue-300 bg-blue-50 hover:bg-blue-100',
            'occupied' => 'border-red-300 bg-red-50 hover:bg-red-100',
            'under_inspection' => 'border-yellow-300 bg-yellow-50 hover:bg-yellow-100',
            'maintenance' => 'border-gray-300 bg-gray-50 hover:bg-gray-100',
        ];
        $dotColors = ['available'=>'bg-green-500','reserved'=>'bg-blue-500','occupied'=>'bg-red-500','under_inspection'=>'bg-yellow-500','maintenance'=>'bg-gray-400'];
    @endphp
    <div @click="openRoom({{ $room->toJson() }}, '{{ $room->roomType->name }}', {{ $room->roomType->base_price }})"
         class="cursor-pointer border-2 {{ $colors[$room->status] ?? 'border-gray-300 bg-gray-50' }} rounded-xl p-4 transition-all duration-200 select-none">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xl font-bold text-gray-800">{{ $room->room_number }}</span>
            <span class="w-3 h-3 rounded-full {{ $dotColors[$room->status] ?? 'bg-gray-400' }}"></span>
        </div>
        <div class="text-xs text-gray-500">{{ $room->roomType->name }}</div>
        <div class="text-xs text-gray-400 mt-0.5">الطابق {{ $room->floor }}</div>
        <div class="mt-2 text-xs font-medium text-gray-700">{{ number_format($room->roomType->base_price, 0) }} ر.ي</div>
    </div>
    @empty
    <div class="col-span-full text-center py-12 text-gray-400">لا توجد غرف مطابقة للتصفية</div>
    @endforelse
</div>

<!-- Room Detail Modal -->
<div x-show="modalOpen" x-cloak
     class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
     @click.self="modalOpen=false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-800">غرفة <span x-text="selectedRoom.room_number"></span></h3>
            <button @click="modalOpen=false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-3">
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="text-gray-500 text-xs mb-1">رقم الغرفة</div>
                    <div class="font-semibold" x-text="selectedRoom.room_number"></div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="text-gray-500 text-xs mb-1">الطابق</div>
                    <div class="font-semibold" x-text="selectedRoom.floor"></div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="text-gray-500 text-xs mb-1">النوع</div>
                    <div class="font-semibold" x-text="selectedRoomType"></div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="text-gray-500 text-xs mb-1">السعر/ليلة</div>
                    <div class="font-semibold" x-text="selectedRoomPrice.toLocaleString() + ' ر.ي'"></div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600">الحالة الحالية:</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                      :class="{
                          'bg-green-100 text-green-800': selectedRoom.status === 'available',
                          'bg-blue-100 text-blue-800': selectedRoom.status === 'reserved',
                          'bg-red-100 text-red-800': selectedRoom.status === 'occupied',
                          'bg-yellow-100 text-yellow-800': selectedRoom.status === 'under_inspection',
                          'bg-gray-100 text-gray-800': selectedRoom.status === 'maintenance',
                      }"
                      x-text="statusLabels[selectedRoom.status] || selectedRoom.status"></span>
            </div>
            @canany(['rooms.manage','rooms.maintenance'])
            <form :action="`/rooms/${selectedRoom.id}/status`" method="POST" class="border-t border-gray-100 pt-3 mt-3">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-1.5">تغيير الحالة</label>
                <div class="flex gap-2">
                    <select name="status" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                        <option value="available">متاحة</option>
                        <option value="under_inspection">تحت الفحص</option>
                        <option value="maintenance">صيانة</option>
                        <option value="reserved">محجوزة</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm hover:bg-primary-700 transition">حفظ</button>
                </div>
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
        modalOpen: false,
        selectedRoom: {},
        selectedRoomType: '',
        selectedRoomPrice: 0,
        statusLabels: {
            available: 'متاحة', reserved: 'محجوزة', occupied: 'مشغولة',
            under_inspection: 'تحت الفحص', maintenance: 'صيانة'
        },
        init() {},
        openRoom(room, type, price) {
            this.selectedRoom = room;
            this.selectedRoomType = type;
            this.selectedRoomPrice = price;
            this.modalOpen = true;
        }
    }
}
</script>
@endpush
