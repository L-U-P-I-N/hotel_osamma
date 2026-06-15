@extends('layouts.app')
@section('title', 'تقرير الإشغال')
@section('page-title', 'تقرير الإشغال')

@section('content')
<div class="space-y-5">

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">من تاريخ</label>
            <input type="date" name="from" value="{{ $from }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">إلى تاريخ</label>
            <input type="date" name="to" value="{{ $to }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
        </div>
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">عرض</button>
    </form>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 text-center">
        <div class="text-3xl font-bold" style="color:#0F4C75;">{{ $totalRooms }}</div>
        <div class="text-sm text-gray-500 mt-1">إجمالي الغرف</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 text-center">
        <div class="text-3xl font-bold text-green-600">{{ $dailyOccupancy->count() > 0 ? round($dailyOccupancy->avg('percent')) : 0 }}%</div>
        <div class="text-sm text-gray-500 mt-1">متوسط الإشغال</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 text-center">
        <div class="text-3xl font-bold text-blue-600">{{ $dailyOccupancy->count() }}</div>
        <div class="text-sm text-gray-500 mt-1">عدد الأيام</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h3 class="font-semibold text-gray-700 mb-4">منحنى الإشغال اليومي</h3>
    <div style="height:256px;">
        <canvas id="occupancyChart"></canvas>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">تفاصيل الإشغال اليومي</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">نسبة الإشغال</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">شريط</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($dailyOccupancy as $day)
                <tr>
                    <td class="px-4 py-3 text-gray-600">{{ $day['date'] }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $day['percent'] }}%</td>
                    <td class="px-4 py-3 w-64">
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="h-2 rounded-full" style="width:{{ $day['percent'] }}%; background:#0F4C75;"></div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">لا توجد بيانات للفترة المحددة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
new Chart(document.getElementById('occupancyChart'), {
    type: 'line',
    data: {
        labels: {!! json_encode($dailyOccupancy->pluck('date')) !!},
        datasets: [{
            label: 'نسبة الإشغال %',
            data: {!! json_encode($dailyOccupancy->pluck('percent')) !!},
            borderColor: '#0F4C75',
            backgroundColor: 'rgba(15,76,117,0.1)',
            fill: true, tension: 0.4, pointRadius: 3,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } } },
        plugins: { legend: { display: false } }
    }
});
</script>
@endpush
