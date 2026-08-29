<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    @font-face {
        font-family: 'NotoNaskh';
        font-style: normal; font-weight: normal;
        src: url('{{ storage_path('fonts/NotoNaskhArabic.ttf') }}') format('truetype');
    }
    @font-face {
        font-family: 'NotoNaskh';
        font-style: normal; font-weight: bold;
        src: url('{{ storage_path('fonts/NotoNaskhArabic-Bold.ttf') }}') format('truetype');
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'NotoNaskh', Arial, sans-serif; font-size: 9px; color: #1f2937; direction: rtl; padding: 14px; }
    .header { text-align: center; padding-bottom: 10px; border-bottom: 3px solid #0F4C75; margin-bottom: 14px; }
    .header h1 { font-size: 16px; font-weight: bold; color: #0F4C75; }
    .header p { font-size: 10px; color: #6b7280; margin-top: 2px; }

    table.grid { width: 100%; border-collapse: collapse; direction: rtl; }
    table.grid thead th { background: #0F4C75; color: #fff; padding: 3px 2px; text-align: center; border: 1px solid #0a3a5e; }
    table.grid tbody td { padding: 3px 2px; text-align: center; border: 1px solid #e0e0e0; }
    table.grid tbody tr:nth-child(even) { background: #f4f8fc; }
    td.name { text-align: right; font-weight: bold; padding-right: 6px; white-space: nowrap; }
    td.tot { font-weight: bold; }
    .legend { margin-top: 10px; font-size: 9px; color: #555; }
    .legend span { margin-left: 14px; }
    .footer { margin-top: 10px; border-top: 1px solid #eee; padding-top: 5px; font-size: 8px; color: #aaa; text-align: right; }
</style>
</head>
<body>

<div class="header">
    @include('partials.pdf-hotel-header')
    <h1>كشف الحضور والغياب الشهري</h1>
    <p>{{ \App\Models\Salary::monthName($month) }} {{ $year }}</p>
</div>

{{--
    dompdf لا يعكس ترتيب أعمدة الجدول بحسب dir="rtl" — نكتب الأعمدة بترتيب معكوس
    (الإجماليات ثم الأيام تنازلياً ثم اسم الموظف أخيراً) حتى يظهر اسم الموظف في
    أقصى اليمين واليوم 1 يليه مباشرةً، كالمعتاد عربياً.
--}}
<table class="grid">
    <thead>
        <tr>
            <th>تأخير</th>
            <th>غياب</th>
            <th>حضور</th>
            @for($d = $daysInMonth; $d >= 1; $d--)
            <th>{{ $d }}</th>
            @endfor
            <th>الموظف</th>
        </tr>
    </thead>
    <tbody>
        @foreach($employees as $emp)
        @php
            $empPresent = 0; $empAbsent = 0; $empLate = 0;
            $marks = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $st = ($records[$emp->id . '_' . $d] ?? null)?->status;
                if ($st === 'present') $empPresent++;
                if ($st === 'absent')  $empAbsent++;
                if ($st === 'late')    $empLate++;
                $marks[$d] = match($st) {
                    'present' => 'ح', 'absent' => 'غ', 'late' => 'ت',
                    'leave' => 'إ', 'holiday' => '-', default => '',
                };
            }
        @endphp
        <tr>
            <td class="tot" style="color:#b45309;">{{ $empLate }}</td>
            <td class="tot" style="color:#dc2626;">{{ $empAbsent }}</td>
            <td class="tot" style="color:#16a34a;">{{ $empPresent }}</td>
            @for($d = $daysInMonth; $d >= 1; $d--)
            <td>{{ $marks[$d] }}</td>
            @endfor
            <td class="name">{{ $emp->name }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="legend">
    <span><b>ح</b> حاضر</span>
    <span><b>غ</b> غائب</span>
    <span><b>ت</b> متأخر</span>
    <span><b>إ</b> إجازة</span>
    <span><b>-</b> عطلة</span>
</div>

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }}</div>

</body>
</html>
