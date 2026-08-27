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
        كل الغرف — يوم العمل {{ \Carbon\Carbon::parse($date)->format('Y/m/d') }} الساعة 1 ظهراً
        إلى {{ \Carbon\Carbon::parse($date)->addDay()->format('Y/m/d') }} الساعة 1 ظهراً
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
            <th style="width:13%;">مديونيته</th>
            <th style="width:18%;">من استلمها وتاريخها</th>
            <th style="width:11%;">دفعات اليوم</th>
            <th style="width:11%;">متى موعد خروجه</th>
            <th style="width:11%;">متى دخل</th>
            <th style="width:16%;">من حاجزها اليوم</th>
            <th style="width:10%;">حالة الغرفة</th>
            <th style="width:8%;">الغرفة</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            <td class="c" style="font-weight:bold;color:{{ ($row['today']['remaining'] ?? 0) > 0 ? '#dc2626' : '#888' }};">
                {{ $row['today'] ? number_format($row['today']['remaining'], 0) . ' ' . $row['today']['currency'] : '—' }}
            </td>
            <td class="c">
                @if($row['today'])
                    @forelse($row['today']['todays_payments'] as $tp)
                        @if(!$loop->first)<hr style="border:none;border-top:1px solid #ddd;margin:3px 0;">@endif
                        {{ $tp['received_by'] ?? '—' }}
                        <div class="sub-note">{{ $tp['time']?->format('d/m/Y H:i') }}</div>
                    @empty
                        —
                    @endforelse
                @else — @endif
            </td>
            <td class="c" style="font-weight:bold;color:#16a34a;">
                @if($row['today'])
                    @forelse($row['today']['todays_payments'] as $tp)
                        @if(!$loop->first)<hr style="border:none;border-top:1px solid #ddd;margin:3px 0;">@endif
                        {{ number_format($tp['amount'], 0) }} {{ $row['today']['currency'] }}
                    @empty
                        —
                    @endforelse
                @else — @endif
            </td>
            <td class="c">
                @if($row['today'])
                    {{ $row['today']['check_out_date']?->format('d/m/Y') ?? '—' }}
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
