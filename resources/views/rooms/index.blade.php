@extends('layouts.app')
@section('title', 'الغرف')
@section('page-title', 'إدارة الغرف')

@section('content')
<div x-data="roomsPage()" x-init="init()">

<!-- Header -->
<div class="flex items-center justify-between mb-5 gap-3 flex-wrap">
    <p class="text-sm text-gray-500">إجمالي الغرف: {{ $rooms->count() }}</p>
    <div class="flex items-center gap-2">
        @can('rooms.edit')
        <button @click="bulkPriceModal=true"
                class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm transition border" style="border-color:#0F4C75; color:#0F4C75;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a2 2 0 014-4z"/></svg>
            تعديل الأسعار بالجملة
        </button>
        @endcan
        @can('rooms.create')
        <a href="{{ route('rooms.create') }}" class="flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            إضافة غرفة
        </a>
        @endcan
    </div>
</div>

<!-- Filter Bar -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">الحالة</label>
            <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                <option value="">جميع الحالات</option>
                <option value="available" {{ request('status')=='available'?'selected':'' }}>متاحة</option>
                <option value="occupied" {{ request('status')=='occupied'?'selected':'' }}>مشغولة</option>
                <option value="under_inspection" {{ request('status')=='under_inspection'?'selected':'' }}>تحت الفحص</option>
                <option value="maintenance" {{ request('status')=='maintenance'?'selected':'' }}>صيانة</option>
            </select>
        </div>
        <div class="flex-1 min-w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">الفئة</label>
            <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                <option value="">جميع الأنواع</option>
                @foreach($roomTypes as $type)
                <option value="{{ $type->name }}" {{ request('type')==$type->name?'selected':'' }}>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">نوع الغرفة</label>
            <select name="sub_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                <option value="">جميع التصنيفات</option>
                <option value="regular"   {{ request('sub_type')==='regular'   ? 'selected' : '' }}>عادية</option>
                <option value="double"    {{ request('sub_type')==='double'    ? 'selected' : '' }}>زوجية</option>
                <option value="suite_a"   {{ request('sub_type')==='suite_a'   ? 'selected' : '' }}>جناح A</option>
                <option value="suite_b"   {{ request('sub_type')==='suite_b'   ? 'selected' : '' }}>جناح B</option>
                <option value="hall"      {{ request('sub_type')==='hall'      ? 'selected' : '' }}>صالة</option>
                <option value="apartment" {{ request('sub_type')==='apartment' ? 'selected' : '' }}>شقة</option>
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

<!-- Bulk Select Bar (shown when selectMode is on) -->
<div x-show="selectMode" x-cloak class="bg-blue-50 border border-blue-200 rounded-xl p-3 mb-4 flex items-center gap-3 flex-wrap">
    <button @click="toggleAll()" class="text-sm font-medium px-3 py-1.5 rounded-lg transition"
            :class="allSelected ? 'bg-blue-600 text-white' : 'bg-white border border-blue-300 text-blue-700'">
        <span x-text="allSelected ? 'إلغاء تحديد الكل' : 'تحديد الكل'"></span>
    </button>
    <div class="flex gap-2 flex-wrap">
        @foreach(['regular'=>'عادية','double'=>'زوجية','suite_a'=>'جناح A','suite_b'=>'جناح B','hall'=>'صالة','apartment'=>'شقة'] as $val=>$label)
        <button @click="selectBySubType('{{ $val }}')" class="text-xs px-2.5 py-1.5 bg-white border border-blue-200 text-blue-700 rounded-lg hover:bg-blue-100 transition">{{ $label }}</button>
        @endforeach
    </div>
    <span class="text-sm text-blue-700 font-medium mr-auto">
        تم تحديد <span x-text="selectedIds.length"></span> غرفة
    </span>
    <button x-show="selectedIds.length > 0" @click="openBulkPrice()"
            class="px-4 py-1.5 text-white text-sm font-medium rounded-lg transition" style="background:#0F4C75;">
        تحديث السعر (<span x-text="selectedIds.length"></span>)
    </button>
    <button @click="selectMode=false; selectedIds=[]" class="text-sm text-gray-500 hover:text-gray-700 px-2">إلغاء</button>
</div>

<!-- Status Legend -->
<div class="flex flex-wrap items-center gap-3 mb-4 text-xs">
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-green-500"></span>متاحة</span>
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500"></span>مشغولة</span>
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-yellow-500"></span>تحت الفحص</span>
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-gray-400"></span>صيانة</span>
    @can('rooms.edit')
    <button @click="selectMode=!selectMode; if(!selectMode) selectedIds=[]"
            class="mr-auto flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition border"
            :class="selectMode ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-300 text-gray-600 hover:border-blue-400'">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        <span x-text="selectMode ? 'إلغاء التحديد' : 'وضع التحديد'"></span>
    </button>
    @endcan
</div>

<!-- Rooms Grid -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
    @forelse($rooms as $room)
    @php
        $colors = [
            'available' => 'border-green-300 bg-green-50 hover:bg-green-100',
            'occupied' => 'border-red-300 bg-red-50 hover:bg-red-100',
            'under_inspection' => 'border-yellow-300 bg-yellow-50 hover:bg-yellow-100',
            'maintenance' => 'border-gray-300 bg-gray-50 hover:bg-gray-100',
        ];
        $dotColors = ['available'=>'bg-green-500','occupied'=>'bg-red-500','under_inspection'=>'bg-yellow-500','maintenance'=>'bg-gray-400'];
    @endphp
    <div class="border-2 {{ $colors[$room->status] ?? 'border-gray-300 bg-gray-50' }} rounded-xl transition-all duration-200 relative"
         :class="selectedIds.includes({{ $room->id }}) ? 'ring-2 ring-blue-500 ring-offset-1' : ''">
        <!-- Checkbox overlay in select mode -->
        <div x-show="selectMode" class="absolute top-2 right-2 z-10"
             @click.stop="toggleSelect({{ $room->id }})">
            <div class="w-5 h-5 rounded border-2 flex items-center justify-center cursor-pointer transition"
                 :class="selectedIds.includes({{ $room->id }}) ? 'bg-blue-600 border-blue-600' : 'bg-white border-gray-400'">
                <svg x-show="selectedIds.includes({{ $room->id }})" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
            </div>
        </div>
        <div @click="selectMode ? toggleSelect({{ $room->id }}) : openRoom({{ $room->toJson() }}, '{{ $room->roomType->name }}', {{ $room->roomType->base_price ?? 0 }})"
             class="cursor-pointer p-4 select-none">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xl font-bold text-gray-800">{{ $room->room_number }}</span>
                <span class="w-3 h-3 rounded-full {{ $dotColors[$room->status] ?? 'bg-gray-400' }}"></span>
            </div>
            <div class="text-xs text-gray-500">{{ $room->roomType->name }}</div>
            <div class="text-xs text-gray-400 mt-0.5">الطابق {{ $room->floor }}</div>
            @if($room->room_sub_type && $room->room_sub_type !== 'regular')
            @php $subBadge = ['double'=>['label'=>'زوجية','cls'=>'bg-pink-100 text-pink-700'],'suite_a'=>['label'=>'جناح A','cls'=>'bg-blue-100 text-blue-700'],'suite_b'=>['label'=>'جناح B','cls'=>'bg-purple-100 text-purple-700'],'hall'=>['label'=>'صالة','cls'=>'bg-gray-100 text-gray-600'],'apartment'=>['label'=>'شقة','cls'=>'bg-amber-100 text-amber-700']][$room->room_sub_type] ?? null; @endphp
            @if($subBadge)
            <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-xs font-medium {{ $subBadge['cls'] }}">{{ $subBadge['label'] }}</span>
            @endif
            @endif
            @if(($room->beds_count ?? 1) > 1)
            <div class="mt-1 text-[11px] text-gray-500">{{ $room->beds_count }} أسرة</div>
            @endif
            <div class="mt-1 text-xs font-medium text-gray-700">{{ number_format($room->priceFor('YER'), 0) }} ر.ي</div>
        </div>
        @canany(['rooms.edit','rooms.delete'])
        <div x-show="!selectMode" class="flex border-t border-gray-200 divide-x divide-x-reverse divide-gray-200">
            @can('rooms.edit')
            <a href="{{ route('rooms.edit', $room) }}" class="flex-1 text-center py-1.5 text-xs font-medium hover:bg-white transition" style="color:#0F4C75;">تعديل</a>
            @endcan
            @can('rooms.delete')
            <button @click.stop="confirmDelete({{ $room->id }}, '{{ $room->room_number }}')"
                    class="flex-1 text-center py-1.5 text-xs font-medium text-red-500 hover:bg-white transition">حذف</button>
            @endcan
        </div>
        @endcanany
    </div>
    @empty
    <div class="col-span-full text-center py-12 text-gray-400">لا توجد غرف مطابقة للتصفية</div>
    @endforelse
</div>

<!-- Bulk Price Modal -->
@can('rooms.edit')
<div x-show="bulkPriceModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="bulkPriceModal=false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-bold text-gray-800">تعديل الأسعار بالجملة</h3>
            <button @click="bulkPriceModal=false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('rooms.bulkPrice') }}">
            @csrf
            <!-- If coming from select mode, send room IDs; otherwise filter by sub_type -->
            <template x-if="selectedIds.length > 0">
                <div>
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="room_ids[]" :value="id">
                    </template>
                    <div class="bg-blue-50 rounded-lg p-3 mb-4 text-sm text-blue-700 text-center">
                        سيتم تحديث <strong x-text="selectedIds.length"></strong> غرفة محددة
                    </div>
                </div>
            </template>
            <template x-if="selectedIds.length === 0">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">تطبيق على</label>
                    <select name="sub_type" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">جميع الغرف</option>
                        <option value="regular">عادية فقط</option>
                        <option value="double">زوجية فقط</option>
                        <option value="suite_a">جناح A فقط</option>
                        <option value="suite_b">جناح B فقط</option>
                        <option value="hall">صالة فقط</option>
                        <option value="apartment">شقة فقط</option>
                    </select>
                </div>
            </template>
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">السعر الجديد (ر.ي / ليلة)</label>
                <input type="number" name="price_yer" required min="0" step="1"
                       placeholder="مثال: 15000"
                       class="w-full border-2 border-gray-300 rounded-xl px-4 py-3 text-lg font-bold text-center outline-none focus:border-primary-500">
            </div>
            <button type="submit" class="w-full text-white py-2.5 rounded-lg font-semibold text-sm transition" style="background:#0F4C75;">
                تحديث الأسعار
            </button>
        </form>
    </div>
</div>
@endcan

<!-- Delete Confirm Modal -->
@can('rooms.delete')
<div x-show="deleteModal" x-cloak
     class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
     @click.self="deleteModal=false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </div>
        <h3 class="font-bold text-gray-800 mb-2">تأكيد الحذف</h3>
        <p class="text-sm text-gray-600 mb-5">هل أنت متأكد من حذف الغرفة <strong x-text="deleteRoomNumber"></strong>؟<br><span class="text-xs text-red-500">لا يمكن التراجع عن هذا الإجراء</span></p>
        <form :action="`/rooms/${deleteRoomId}`" method="POST" class="flex gap-3 justify-center">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-5 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition font-medium">نعم، احذف</button>
            <button type="button" @click="deleteModal=false" class="px-5 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition">إلغاء</button>
        </form>
    </div>
</div>
@endcan

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
                    <div class="text-gray-500 text-xs mb-1">التصنيف</div>
                    <div class="font-semibold" x-text="subTypeLabels[selectedRoom.room_sub_type] || 'عادية'"></div>
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
                          'bg-red-100 text-red-800': selectedRoom.status === 'occupied',
                          'bg-yellow-100 text-yellow-800': selectedRoom.status === 'under_inspection',
                          'bg-gray-100 text-gray-800': selectedRoom.status === 'maintenance',
                      }"
                      x-text="statusLabels[selectedRoom.status] || selectedRoom.status"></span>
            </div>
            @canany(['rooms.edit','rooms.maintenance'])
            <form :action="`/rooms/${selectedRoom.id}/status`" method="POST" class="border-t border-gray-100 pt-3 mt-3">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-1.5">تغيير الحالة</label>
                <div class="flex gap-2">
                    <select name="status" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                        <option value="available">متاحة</option>
                        <option value="under_inspection">تحت الفحص</option>
                        <option value="maintenance">صيانة</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm hover:bg-primary-700 transition">حفظ</button>
                </div>
                <p class="text-xs text-gray-400 mt-1">مشغولة تتغير تلقائياً عبر الحجوزات</p>
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
        deleteModal: false,
        deleteRoomId: null,
        deleteRoomNumber: '',
        selectedRoom: {},
        selectedRoomType: '',
        selectedRoomPrice: 0,
        selectMode: false,
        selectedIds: [],
        bulkPriceModal: false,
        allRoomIds: @json($rooms->pluck('id')),
        roomSubTypes: @json($rooms->pluck('room_sub_type', 'id')),
        statusLabels: {
            available: 'متاحة', occupied: 'مشغولة',
            under_inspection: 'تحت الفحص', maintenance: 'صيانة'
        },
        subTypeLabels: {
            regular: 'عادية', double: 'زوجية', suite_a: 'جناح A',
            suite_b: 'جناح B', hall: 'صالة', apartment: 'شقة'
        },
        get allSelected() {
            return this.allRoomIds.length > 0 && this.selectedIds.length === this.allRoomIds.length;
        },
        init() {},
        openRoom(room, type, price) {
            if (this.selectMode) { this.toggleSelect(room.id); return; }
            this.selectedRoom = room;
            this.selectedRoomType = type;
            this.selectedRoomPrice = price;
            this.modalOpen = true;
        },
        confirmDelete(id, number) {
            this.deleteRoomId = id;
            this.deleteRoomNumber = number;
            this.deleteModal = true;
        },
        toggleSelect(id) {
            const idx = this.selectedIds.indexOf(id);
            if (idx === -1) this.selectedIds.push(id);
            else this.selectedIds.splice(idx, 1);
        },
        toggleAll() {
            if (this.allSelected) this.selectedIds = [];
            else this.selectedIds = [...this.allRoomIds];
        },
        selectBySubType(subType) {
            const ids = Object.entries(this.roomSubTypes)
                .filter(([id, st]) => st === subType || (!st && subType === 'regular'))
                .map(([id]) => parseInt(id));
            // Toggle: if all already selected, deselect; otherwise add
            const allIn = ids.every(id => this.selectedIds.includes(id));
            if (allIn) this.selectedIds = this.selectedIds.filter(id => !ids.includes(id));
            else ids.forEach(id => { if (!this.selectedIds.includes(id)) this.selectedIds.push(id); });
        },
        openBulkPrice() {
            this.bulkPriceModal = true;
        }
    }
}
</script>
@endpush
