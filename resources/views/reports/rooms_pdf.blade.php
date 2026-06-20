<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    @font-face {
        font-family: 'NotoNaskhArabic';
        font-style: normal; font-weight: normal;
        src: url("{{ storage_path('fonts') }}/NotoNaskhArabic.ttf") format('truetype');
    }
    @font-face {
        font-family: 'NotoNaskhArabic';
        font-style: normal; font-weight: bold;
        src: url("{{ storage_path('fonts') }}/NotoNaskhArabic-Bold.ttf") format('truetype');
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'NotoNaskhArabic', sans-serif; font-size: 10px; direction: rtl; color: #1a1a1a; background: #fff; padding: 16px; }
    .header { text-align: center; border-bottom: 2px solid #0F4C75; padding-bottom: 10px; margin-bottom: 14px; }
    .header h1 { font-size: 16px; color: #0F4C75; font-weight: bold; }
    .header .sub { font-size: 9px; color: #555; margin-top: 4px; }
    table.data { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 14px; direction: rtl; }
    table.data thead tr { background: #0F4C75; color: #fff; }
    table.data thead th { padding: 5px 6px; font-weight: bold; border: 1px solid #0a3a5e; text-align: right; white-space: nowrap; }
    table.data tbody tr:nth-child(even) { background: #f4f8fc; }
    table.data tbody td { padding: 4px 6px; border: 1px solid #e0e0e0; text-align: right; white-space: nowrap; }
    .total-row td { font-weight: bold; background: #f0f4f8 !important; }
    .footer { margin-top: 12px; border-top: 1px solid #eee; padding-top: 6px; font-size: 8px; color: #aaa; text-align: right; }
</style>
</head>
<body>
<div class="header">
    <h1>تقرير أداء الغرف</h1>
    <div class="sub">الفترة: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>
</div>

@php
    $statusLabels = ['available'=>'متاحة','occupied'=>'مشغولة','maintenance'=>'صيانة','under_inspection'=>'فحص'];
    $totalRes = $rooms->sum('total_reservations');
    $totalRev = $rooms->sum('total_revenue');
@endphp

<table class="data">
    <thead>
        <tr>
            <th>رقم الغرفة</th>
            <th>النوع</th>
            <th>الحالة</th>
            <th>عدد الحجوزات</th>
            <th>الإيرادات (ر.ي)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rooms as $room)
        <tr>
            <td>{{ $room->room_number }}</td>
            <td>{{ $room->roomType?->name ?? '—' }}</td>
            <td>{{ $statusLabels[$room->status] ?? $room->status }}</td>
            <td>{{ $room->total_reservations ?? 0 }}</td>
            <td>{{ number_format($room->total_revenue ?? 0, 0) }}</td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center; padding:10px; color:#999;">لا توجد بيانات</td></tr>
        @endforelse
        @if($rooms->isNotEmpty())
        <tr class="total-row">
            <td colspan="3">الإجمالي</td>
            <td>{{ $totalRes }}</td>
            <td>{{ number_format($totalRev, 0) }}</td>
        </tr>
        @endif
    </tbody>
</table>

<div class="footer">
    طُبع بتاريخ: {{ now()->format('d/m/Y H:i') }}
</div>
</body>
</html>
