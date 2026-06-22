@extends('layouts.app')
@section('title', 'إيراد الغرف')
@section('page-title', 'تقرير إيراد الغرف — المتوقع vs الفعلي')

@section('content')
<div class="mb-5 flex gap-3">
    <div class="flex gap-2 items-center">
        <label class="text-sm text-gray-600">من:</label>
        <input type="date" id="fromDate" value="{{ $from }}" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm">
    </div>
    <div class="flex gap-2 items-center">
        <label class="text-sm text-gray-600">إلى:</label>
        <input type="date" id="toDate" value="{{ $to }}" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm">
    </div>
    <button onclick="filterByDate()" class="px-4 py-1.5 bg-primary-600 text-white rounded-lg text-sm">فلتر</button>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
    <div class="bg-white rounded-xl border border-green-200 shadow-sm p-4">
        <p class="text-xs text-gray-500 mb-1">الإيراد الفعلي</p>
        <p class="text-2xl font-bold text-green-600">{{ number_format($totalActual, 0) }} <span class="text-sm font-normal">ر.ي</span></p>
    </div>
    <div class="bg-white rounded-xl border border-blue-200 shadow-sm p-4">
        <p class="text-xs text-gray-500 mb-1">الإيراد المتوقع</p>
        <p class="text-2xl font-bold text-blue-600">{{ number_format($totalExpected, 0) }} <span class="text-sm font-normal">ر.ي</span></p>
    </div>
    <div class="bg-white rounded-xl border shadow-sm p-4">
        @php $achievement = $totalExpected > 0 ? round(($totalActual / $totalExpected) * 100) : 0; @endphp
        <p class="text-xs text-gray-500 mb-1">نسبة التحقق</p>
        <p class="text-2xl font-bold {{ $achievement >= 100 ? 'text-green-600' : ($achievement >= 80 ? 'text-amber-600' : 'text-red-600') }}">{{ $achievement }}%</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">تفصيل الإيراد حسب نوع الغرفة</h3>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">نوع الغرفة</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">عدد الغرف</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">عدد الحجوزات</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">ليالي</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">الإيراد الفعلي</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">الإيراد المتوقع</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">ADR</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-600">التحقق</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($revenueByType as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $row['type']->name }}</td>
                    <td class="px-4 py-3 text-center">{{ $row['rooms_count'] }}</td>
                    <td class="px-4 py-3 text-center">{{ $row['reservation_count'] }}</td>
                    <td class="px-4 py-3 text-center text-gray-600">{{ $row['total_nights'] }}</td>
                    <td class="px-4 py-3 text-center font-bold text-green-600">{{ number_format($row['actual_revenue'], 0) }}</td>
                    <td class="px-4 py-3 text-center font-bold text-blue-600">{{ number_format($row['expected_revenue'], 0) }}</td>
                    <td class="px-4 py-3 text-center font-semibold">{{ number_format($row['adr'], 0) }} ر.ي</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-lg text-xs font-bold {{ $row['achievement'] >= 100 ? 'bg-green-100 text-green-700' : ($row['achievement'] >= 80 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                            {{ $row['achievement'] }}%
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
function filterByDate() {
    const from = document.getElementById('fromDate').value;
    const to = document.getElementById('toDate').value;
    window.location.href = `{{ route('reports.roomRevenue') }}?from=${from}&to=${to}`;
}
</script>
@endsection
