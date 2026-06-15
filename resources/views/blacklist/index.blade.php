@extends('layouts.app')
@section('title', 'القائمة السوداء')
@section('page-title', 'القائمة السوداء')

@section('content')
<div x-data="{ addModal: false }">

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" class="flex gap-3 items-end">
        <div class="flex-1">
            <label class="block text-xs font-medium text-gray-600 mb-1">بحث بالاسم</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="ابحث عن نزيل..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
        </div>
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">بحث</button>
        <a href="{{ route('blacklist.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm hover:bg-gray-200 transition">إعادة تعيين</a>
    </form>
</div>

<div class="flex justify-between items-center mb-4">
    <h3 class="font-semibold text-gray-700">النزلاء في القائمة السوداء
        <span class="text-gray-400 font-normal text-sm mr-1">({{ $guests->total() }})</span>
    </h3>
    <button @click="addModal=true" class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
        إضافة للقائمة السوداء
    </button>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100"><tr>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الاسم</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الجنسية</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">رقم الهوية (مخفي)</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">سبب الحظر</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">تاريخ الحظر</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إجراءات</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($guests as $guest)
                @php
                    $id = $guest->id_number;
                    $masked = str_repeat('*', max(0, mb_strlen($id) - 4)) . mb_substr($id, -4);
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-800">{{ $guest->full_name }}</div>
                        <div class="text-xs text-gray-400">{{ $guest->getIdTypeLabel() }}</div>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $guest->nationality ?: '-' }}</td>
                    <td class="px-4 py-3 font-mono text-gray-500 text-xs">{{ $masked }}</td>
                    <td class="px-4 py-3 text-red-700 text-xs max-w-xs">{{ $guest->blacklist_reason ?: '-' }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $guest->blacklisted_at?->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('blacklist.remove', $guest) }}"
                              onsubmit="return confirm('هل تريد إزالة هذا النزيل من القائمة السوداء؟')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-green-600 hover:text-green-800 text-xs font-medium">إزالة</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">القائمة السوداء فارغة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($guests->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $guests->links() }}</div>
    @endif
</div>

<!-- Add Modal -->
<div x-show="addModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="addModal=false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-800">إضافة نزيل للقائمة السوداء</h3>
            <button @click="addModal=false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('blacklist.store') }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">معرف النزيل في النظام *</label>
                <input type="number" name="guest_id" required placeholder="رقم النزيل (من صفحة الحجوزات)"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none">
                <p class="text-xs text-gray-400 mt-1">يمكن الاطلاع على رقم النزيل من صفحة تفاصيل الحجز</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">سبب الحظر *</label>
                <textarea name="reason" required rows="3"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none resize-none"
                          placeholder="اذكر سبب الحظر بالتفصيل..."></textarea>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-xs text-red-700">
                تحذير: بعد الإضافة لن يتمكن هذا النزيل من الحجز في الفندق، وستظهر تحذيرات عند محاولة تسجيل دخوله.
            </div>
            <button type="submit" class="w-full bg-red-600 text-white py-2.5 rounded-lg font-semibold hover:bg-red-700 transition text-sm">إضافة للقائمة السوداء</button>
        </form>
    </div>
</div>

</div>
@endsection
