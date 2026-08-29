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
    table.data { width: 100%; border-collapse: collapse; font-size: 10px; direction: rtl; }
    table.data thead tr { background: #0F4C75; color: #fff; }
    table.data thead th { padding: 6px 8px; font-weight: bold; border: 1px solid #0a3a5e; text-align: right; }
    table.data tbody tr:nth-child(even) { background: #f4f8fc; }
    table.data tbody td { padding: 5px 8px; border: 1px solid #e0e0e0; text-align: right; }
    table.data tbody td.ltr { text-align: left; direction: ltr; }
    .total-row td { font-weight: bold; background: #e8f0f7 !important; color: #0F4C75; }
    .footer { margin-top: 12px; border-top: 1px solid #eee; padding-top: 6px; font-size: 8px; color: #aaa; text-align: right; }
</style>
</head>
<body>

@php
    $statusLabels = ['available' => 'متاحة', 'occupied' => 'مشغولة', 'maintenance' => 'صيانة', 'under_inspection' => 'فحص'];
@endphp

<div class="header">
    @include('partials.pdf-hotel-header')
    <h1>تقرير حالة الغرف</h1>
    <div class="sub">بتاريخ: {{ now()->format('d/m/Y') }}</div>
</div>

{{-- رقم الغرفة وحالتها فقط (دون إيرادات أو عدد حجوزات). العمود الأيمن يُكتب
     أخيراً لأن dompdf يتجاهل dir="rtl" في ترتيب الأعمدة. --}}
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th>الحالة</th>
            <th>رقم الغرفة</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rooms as $room)
        <tr>
            <td>{{ $statusLabels[$room->status] ?? $room->status }}</td>
            <td style="font-weight:bold;">{{ $room->room_number }}</td>
        </tr>
        @empty
        <tr><td colspan="2" style="text-align:center;padding:12px;color:#999;">لا توجد غرف</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }}</div>

</body>
</html>
