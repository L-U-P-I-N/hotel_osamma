@extends('layouts.app')
@section('title', 'الاسترجاعات')
@section('page-title', 'سجل الاسترجاعات')

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
    <div class="bg-white rounded-xl border border-rose-200 shadow-sm p-4">
        <p class="text-xs text-gray-500 mb-1">إجمالي المسترجع</p>
        <p class="text-2xl font-bold text-rose-600">{{ number_format($totalRefunds, 0) }} <span class="text-sm font-normal">ر.ي</span></p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <p class="text-xs text-gray-500 mb-1">عدد العمليات</p>
        <p class="text-2xl font-bold text-gray-700">{{ $refunds->total() }}</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-800">الاسترجاعات</h3>
        <form class="flex gap-2">
            <input type="date" name="from" value="{{ request('from') }}" class="text-sm border border-gray-200 rounded-lg px-3 py-1.5">
            <input type="date" name="to"   value="{{ request('to') }}"   class="text-sm border border-gray-200 rounded-lg px-3 py-1.5">
            <button class="text-sm bg-gray-100 px-3 py-1.5 rounded-lg hover:bg-gray-200">فلتر</button>
        </form>
    </div>

    @if($refunds->isEmpty())
    <div class="py-12 text-center text-gray-400 text-sm">لا توجد استرجاعات</div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50 text-xs text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-right">#</th>
                    <th class="px-4 py-3 text-right">النزيل</th>
                    <th class="px-4 py-3 text-right">الغرفة</th>
                    <th class="px-4 py-3 text-right">المبلغ</th>
                    <th class="px-4 py-3 text-right">الطريقة</th>
                    <th class="px-4 py-3 text-right">السبب</th>
                    <th class="px-4 py-3 text-right">بواسطة</th>
                    <th class="px-4 py-3 text-right">التاريخ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($refunds as $refund)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-400">{{ $refund->id }}</td>
                    <td class="px-4 py-3 font-medium">{{ $refund->reservation?->guest?->full_name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $refund->reservation?->room?->room_number ?? '—' }}</td>
                    <td class="px-4 py-3 font-bold text-rose-600">{{ number_format($refund->amount, 0) }} ر.ي</td>
                    <td class="px-4 py-3">{{ match($refund->method) { 'cash'=>'نقداً','pos'=>'POS','bank_transfer'=>'تحويل', default=>$refund->method } }}</td>
                    <td class="px-4 py-3 text-gray-600 max-w-xs truncate">{{ $refund->reason }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $refund->processedBy?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $refund->refunded_at?->format('d/m/Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3">{{ $refunds->links() }}</div>
    @endif
</div>
@endsection
