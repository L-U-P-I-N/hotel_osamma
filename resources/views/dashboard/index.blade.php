@extends('layouts.app')
@section('title', 'لوحة التحكم')
@section('page-title', 'لوحة التحكم')

@section('content')
<!-- Stat Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 font-medium">نسبة الإشغال</p>
                <p class="text-3xl font-bold text-primary-800 mt-1">{{ $occupancyPercent }}%</p>
                <p class="text-xs text-gray-400 mt-1">{{ $occupiedRooms }} من {{ $totalRooms }} غرفة</p>
            </div>
            <div class="w-12 h-12 bg-primary-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 font-medium">وصول اليوم</p>
                <p class="text-3xl font-bold text-green-600 mt-1">{{ $todayArrivals }}</p>
                <p class="text-xs text-gray-400 mt-1">مغادرة: {{ $todayDepartures }}</p>
            </div>
            <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 font-medium">إيرادات اليوم</p>
                <p class="text-3xl font-bold text-accent-600 mt-1">{{ number_format($todayRevenue, 0) }}</p>
                <p class="text-xs text-gray-400 mt-1">ريال يمني</p>
            </div>
            <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 font-medium">غرف متاحة</p>
                <p class="text-3xl font-bold text-blue-600 mt-1">{{ $availableRooms }}</p>
                <p class="text-xs text-gray-400 mt-1">من {{ $totalRooms }} غرفة إجمالي</p>
            </div>
            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>
</div>

<!-- Charts + Quick Actions -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
    <!-- Room Status Chart -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 mb-4">توزيع حالات الغرف</h3>
        <div class="relative h-48">
            <canvas id="roomStatusChart"></canvas>
        </div>
        <div class="mt-4 space-y-2">
            @php
                $statusLabels = ['available'=>'متاحة','reserved'=>'محجوزة','occupied'=>'مشغولة','under_inspection'=>'تحت الفحص','maintenance'=>'صيانة'];
                $statusColors = ['available'=>'#16a34a','reserved'=>'#2563eb','occupied'=>'#dc2626','under_inspection'=>'#d97706','maintenance'=>'#6b7280'];
            @endphp
            @foreach($roomStatusCounts as $status => $count)
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full" style="background:{{ $statusColors[$status] ?? '#999' }}"></div>
                    <span class="text-gray-600">{{ $statusLabels[$status] ?? $status }}</span>
                </div>
                <span class="font-semibold text-gray-800">{{ $count }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 mb-4">إجراءات سريعة</h3>
        <div class="space-y-3">
            @can('checkin.create')
            <a href="{{ route('checkin.create') }}"
               class="flex items-center gap-3 p-3 bg-primary-50 hover:bg-primary-100 rounded-lg transition-colors">
                <div class="w-9 h-9 bg-primary-600 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                </div>
                <div>
                    <div class="font-medium text-sm text-primary-800">تسجيل الدخول</div>
                    <div class="text-xs text-primary-500">النزيل موجود الآن</div>
                </div>
            </a>
            <a href="{{ route('checkin.create', ['mode' => 'reserve']) }}"
               class="flex items-center gap-3 p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                <div class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <div class="font-medium text-sm text-blue-800">حجز لزبون</div>
                    <div class="text-xs text-blue-500">النزيل سيصل لاحقاً</div>
                </div>
            </a>
            @endcan
            @can('rooms.view')
            <a href="{{ route('rooms.index') }}"
               class="flex items-center gap-3 p-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                <div class="w-9 h-9 bg-green-600 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/></svg>
                </div>
                <div>
                    <div class="font-medium text-sm text-green-800">عرض الغرف</div>
                    <div class="text-xs text-green-500">حالة جميع الغرف</div>
                </div>
            </a>
            @endcan
            @can('settlement.view')
            <a href="{{ route('settlement.index') }}"
               class="flex items-center gap-3 p-3 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors">
                <div class="w-9 h-9 bg-amber-600 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <div class="font-medium text-sm text-amber-800">التسوية النقدية</div>
                    <div class="text-xs text-amber-500">حساب اليوم</div>
                </div>
            </a>
            @endcan
        </div>
    </div>

    <!-- Today Summary -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 mb-4">ملخص اليوم</h3>
        <div class="space-y-4">
            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                <span class="text-sm text-gray-600">إجمالي الغرف</span>
                <span class="font-bold text-gray-800">{{ $totalRooms }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                <span class="text-sm text-gray-600">مشغولة</span>
                <span class="font-bold text-red-600">{{ $roomStatusCounts['occupied'] ?? 0 }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                <span class="text-sm text-gray-600">متاحة</span>
                <span class="font-bold text-green-600">{{ $roomStatusCounts['available'] ?? 0 }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                <span class="text-sm text-gray-600">صيانة</span>
                <span class="font-bold text-gray-600">{{ $roomStatusCounts['maintenance'] ?? 0 }}</span>
            </div>
            <div class="flex justify-between items-center py-2">
                <span class="text-sm text-gray-600">تحت الفحص</span>
                <span class="font-bold text-yellow-600">{{ $roomStatusCounts['under_inspection'] ?? 0 }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Recent Reservations -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-700">آخر الحجوزات</h3>
        @can('checkin.view')
        <a href="{{ route('reservations.index') }}" class="text-sm text-primary-600 hover:text-primary-800 font-medium">عرض الكل</a>
        @endcan
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">النزيل</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الغرفة</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاريخ الدخول</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاريخ الخروج</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المبلغ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($recentReservations as $res)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-3 text-gray-500">#{{ $res->id }}</td>
                    <td class="px-6 py-3 font-medium text-gray-800">{{ $res->guest?->full_name ?? '—' }}</td>
                    <td class="px-6 py-3 text-gray-600">{{ $res->room?->room_number ?? '—' }}</td>
                    <td class="px-6 py-3 text-gray-600">{{ $res->check_in_date?->format('d/m/Y') ?? '—' }}</td>
<td class="px-6 py-3 text-gray-600">{{ $res->check_out_date?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-6 py-3">
                        @php
                            $sc = ['confirmed'=>'bg-blue-100 text-blue-800','checked_in'=>'bg-green-100 text-green-800','checked_out'=>'bg-gray-100 text-gray-800','cancelled'=>'bg-red-100 text-red-800'];
                        @endphp
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $sc[$res->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $res->status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-3 font-medium text-gray-800">{{ number_format($res->total_amount, 0) }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-8 text-center text-gray-400">لا توجد حجوزات حتى الآن</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
const ctx = document.getElementById('roomStatusChart');
if (ctx) {
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['متاحة','محجوزة','مشغولة','تحت الفحص','صيانة'],
            datasets: [{
                data: [
                    {{ $roomStatusCounts['available'] ?? 0 }},
                    {{ $roomStatusCounts['reserved'] ?? 0 }},
                    {{ $roomStatusCounts['occupied'] ?? 0 }},
                    {{ $roomStatusCounts['under_inspection'] ?? 0 }},
                    {{ $roomStatusCounts['maintenance'] ?? 0 }},
                ],
                backgroundColor: ['#16a34a','#2563eb','#dc2626','#d97706','#6b7280'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            cutout: '70%',
        }
    });
}
</script>
@endpush
