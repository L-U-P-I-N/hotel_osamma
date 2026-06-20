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
    body { font-family: 'NotoNaskhArabic', sans-serif; font-size: 9px; direction: rtl; color: #1a1a1a; background: #fff; padding: 14px; }
    .header { text-align: center; border-bottom: 2px solid #0F4C75; padding-bottom: 8px; margin-bottom: 12px; }
    .header h1 { font-size: 15px; color: #0F4C75; font-weight: bold; }
    .header .sub { font-size: 8px; color: #555; margin-top: 3px; }
    .stats { display: table; width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .stats td { border: 1px solid #ddd; padding: 6px 10px; text-align: center; width: 25%; }
    .stats .num { font-size: 14px; font-weight: bold; color: #0F4C75; }
    .stats .lbl { font-size: 7px; color: #666; margin-top: 2px; }
    .section-title { font-size: 10px; font-weight: bold; color: #fff; background: #0F4C75; padding: 4px 8px; margin-bottom: 0; }
    table.data { width: 100%; border-collapse: collapse; font-size: 8.5px; margin-bottom: 12px; direction: rtl; }
    table.data thead tr { background: #e8f0f7; }
    table.data thead th { padding: 4px 6px; font-weight: bold; border: 1px solid #cdd8e3; text-align: right; }
    table.data tbody tr:nth-child(even) { background: #f9fbfd; }
    table.data tbody td { padding: 4px 6px; border: 1px solid #e0e7ef; text-align: right; }
    table.data tbody td.c { text-align: center; }
    .total-row td { font-weight: bold; background: #e8f0f7 !important; color: #0F4C75; }
    .two-col { display: table; width: 100%; border-collapse: separate; border-spacing: 8px; margin-bottom: 12px; }
    .two-col .col { display: table-cell; width: 50%; vertical-align: top; }
    .warning { border: 1px solid #f59e0b; background: #fffbeb; border-radius: 4px; padding: 6px 10px; margin-bottom: 10px; font-size: 8px; color: #92400e; }
    .footer { margin-top: 10px; border-top: 1px solid #eee; padding-top: 4px; font-size: 7px; color: #aaa; text-align: right; }
</style>
</head>
<body>
@php $methodLabels = ['cash' => 'نقدي', 'pos' => 'POS', 'bank_transfer' => 'تحويل بنكي']; @endphp

<div class="header">
    <h1>تقرير الإيرادات</h1>
    <div class="sub">الفترة: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>
</div>

<table class="stats">
    <tr>
        <td><div class="num">{{ number_format($totalRevenue, 0) }} ر.ي</div><div class="lbl">إجمالي الإيرادات</div></td>
        <td><div class="num">{{ $paymentCount }}</div><div class="lbl">عدد الدفعات</div></td>
        <td><div class="num">{{ $reservationCount }}</div><div class="lbl">حجوزات مدفوعة</div></td>
        <td><div class="num">{{ number_format($avgPayment, 0) }} ر.ي</div><div class="lbl">متوسط الدفعة</div></td>
    </tr>
</table>

@if($foreignPayments->isNotEmpty())
<div class="warning">
    <strong>عملات أجنبية (غير مشمولة):</strong>
    @foreach($foreignPayments as $fp)
    &nbsp;|&nbsp; {{ $fp->count }} دفعة · {{ number_format($fp->total, 0) }} {{ $fp->currency === 'SAR' ? 'ر.س' : '$' }}
    @endforeach
</div>
@endif

<div class="two-col">
    <div class="col">
        <div class="section-title">حسب نوع الغرفة</div>
        <table class="data">
            <thead><tr>
                <th>نوع الغرفة</th><th>الحجوزات</th><th>الدفعات</th><th>الإجمالي (ر.ي)</th><th>النسبة</th>
            </tr></thead>
            <tbody>
                @forelse($revenueByType as $r)
                <tr>
                    <td>{{ $r->name }}</td>
                    <td class="c">{{ $r->reservation_count }}</td>
                    <td class="c">{{ $r->payment_count }}</td>
                    <td>{{ number_format($r->total, 0) }}</td>
                    <td class="c">{{ $totalRevenue > 0 ? round(($r->total/$totalRevenue)*100) : 0 }}%</td>
                </tr>
                @empty
                <tr><td colspan="5" class="c" style="color:#999;padding:6px;">لا توجد بيانات</td></tr>
                @endforelse
                @if($revenueByType->isNotEmpty())
                <tr class="total-row">
                    <td>الإجمالي</td>
                    <td class="c">{{ $revenueByType->sum('reservation_count') }}</td>
                    <td class="c">{{ $revenueByType->sum('payment_count') }}</td>
                    <td>{{ number_format($totalRevenue, 0) }}</td>
                    <td class="c">100%</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="col">
        <div class="section-title">حسب طريقة الدفع</div>
        <table class="data">
            <thead><tr>
                <th>الطريقة</th><th>عدد الدفعات</th><th>الإجمالي (ر.ي)</th><th>النسبة</th>
            </tr></thead>
            <tbody>
                @forelse($revenueByMethod as $m)
                <tr>
                    <td>{{ $methodLabels[$m->method] ?? $m->method }}</td>
                    <td class="c">{{ $m->count }}</td>
                    <td>{{ number_format($m->total, 0) }}</td>
                    <td class="c">{{ $totalRevenue > 0 ? round(($m->total/$totalRevenue)*100) : 0 }}%</td>
                </tr>
                @empty
                <tr><td colspan="4" class="c" style="color:#999;padding:6px;">لا توجد بيانات</td></tr>
                @endforelse
                @if($revenueByMethod->isNotEmpty())
                <tr class="total-row">
                    <td>الإجمالي</td>
                    <td class="c">{{ $revenueByMethod->sum('count') }}</td>
                    <td>{{ number_format($totalRevenue, 0) }}</td>
                    <td class="c">100%</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

@if($topRooms->isNotEmpty())
<div class="section-title">أعلى الغرف إيرادًا</div>
<table class="data">
    <thead><tr>
        <th>#</th><th>رقم الغرفة</th><th>نوع الغرفة</th><th>الحجوزات</th><th>الإيراد (ر.ي)</th><th>النسبة</th>
    </tr></thead>
    <tbody>
        @foreach($topRooms as $i => $room)
        <tr>
            <td class="c">{{ $i + 1 }}</td>
            <td style="font-weight:bold;">{{ $room->room_number }}</td>
            <td>{{ $room->type_name }}</td>
            <td class="c">{{ $room->reservation_count }}</td>
            <td>{{ number_format($room->total, 0) }}</td>
            <td class="c">{{ $totalRevenue > 0 ? round(($room->total/$totalRevenue)*100) : 0 }}%</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
