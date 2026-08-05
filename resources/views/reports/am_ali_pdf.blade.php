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
    body { font-family: 'NotoNaskhArabic', sans-serif; font-size: 12px; direction: rtl; color: #1a1a1a; background: #fff; padding: 14px; }
    .header { text-align: center; border-bottom: 2px solid #0F4C75; padding-bottom: 10px; margin-bottom: 14px; }
    .header h1 { font-size: 17px; color: #0F4C75; font-weight: bold; }
    .header .sub { font-size: 10px; color: #555; margin-top: 4px; }
    table.data { width: 100%; border-collapse: collapse; font-size: 11px; direction: rtl; table-layout: fixed; }
    table.data thead tr { background: #1f2937; color: #fff; }
    table.data thead th { padding: 6px 6px; font-weight: bold; border: 1px solid #111827; text-align: center; }
    table.data tbody tr:nth-child(even) { background: #f4f8fc; }
    table.data tbody td { padding: 5px 6px; border: 1px solid #ccc; text-align: right; word-wrap: break-word; word-break: break-word; vertical-align: top; }
    table.data tbody td.c { text-align: center; }
    .sub-note { font-size: 8.5px; color: #666; }
    .empty { text-align: center; color: #999; padding: 16px; }
    .footer { margin-top: 12px; border-top: 1px solid #eee; padding-top: 6px; font-size: 8px; color: #aaa; text-align: right; }
</style>
</head>
<body>

<div class="header">
    @include('partials.pdf-logo')
    <h1>تقرير عم علي</h1>
    <div class="sub">
        مقارنة كل غرفة: نزيل اليوم {{ \Carbon\Carbon::parse($date)->format('Y/m/d') }}
        — نزيل الأمس {{ \Carbon\Carbon::parse($yesterday)->format('Y/m/d') }}
    </div>
</div>

@if($rows->isEmpty())
<p class="empty">لا توجد غرف</p>
@else
{{-- dompdf يتجاهل dir="rtl" في ترتيب الأعمدة، فنكتبها بترتيب معكوس (الأخير منطقياً
     أولاً) ليظهر أول عمود منطقياً في أقصى اليمين. --}}
<table class="data" dir="rtl">
    <thead>
        <tr>
            <th style="width:9%;">مبلغ آخر دفعة أمس</th>
            <th style="width:9%;">مديونية الأمس</th>
            <th style="width:9%;">سدد عند من</th>
            <th style="width:12%;">من أمس كان فيها</th>
            <th style="width:9%;">مديونية اليوم</th>
            <th style="width:9%;">من استلم</th>
            <th style="width:9%;">مبلغ آخر دفعة</th>
            <th style="width:10%;">متى دخل</th>
            <th style="width:12%;">من فيها اليوم</th>
            <th style="width:7%;">حالة الغرفة</th>
            <th style="width:5%;">الغرفة</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            <td class="c" style="font-weight:bold;color:#16a34a;">
                @if($row['yday'])
                    {{ number_format($row['yday']['paid'], 0) . ' ' . $row['yday']['currency'] }}
                    @if($row['yday']['paid_date'])<div class="sub-note">{{ $row['yday']['paid_date']->format('d/m/Y H:i') }}</div>@endif
                @else — @endif
            </td>
            <td class="c" style="font-weight:bold;color:{{ ($row['yday']['remaining'] ?? 0) > 0 ? '#dc2626' : '#888' }};">
                {{ $row['yday'] ? number_format($row['yday']['remaining'], 0) . ' ' . $row['yday']['currency'] : '—' }}
            </td>
            <td class="c">{{ $row['yday']['received_by'] ?? '—' }}</td>
            <td>{{ $row['yday']['guest_name'] ?? '—' }}</td>
            <td class="c" style="font-weight:bold;color:{{ ($row['today']['remaining'] ?? 0) > 0 ? '#dc2626' : '#888' }};">
                {{ $row['today'] ? number_format($row['today']['remaining'], 0) . ' ' . $row['today']['currency'] : '—' }}
            </td>
            <td class="c">{{ $row['today']['received_by'] ?? '—' }}</td>
            <td class="c" style="font-weight:bold;color:#16a34a;">
                @if($row['today'])
                    {{ number_format($row['today']['paid'], 0) . ' ' . $row['today']['currency'] }}
                    @if($row['today']['paid_date'])<div class="sub-note">{{ $row['today']['paid_date']->format('d/m/Y H:i') }}</div>@endif
                @else — @endif
            </td>
            <td class="c">
                @if($row['today'])
                    {{ $row['today']['check_in_date']?->format('d/m/Y') }}<br>{{ $row['today']['check_in_time'] ?? '—' }}
                @else — @endif
            </td>
            <td>{{ $row['today']['guest_name'] ?? '—' }}</td>
            <td class="c">{{ $row['status'] }}</td>
            <td class="c" style="font-weight:bold;">{{ $row['room']->room_number }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }}</div>

</body>
</html>
