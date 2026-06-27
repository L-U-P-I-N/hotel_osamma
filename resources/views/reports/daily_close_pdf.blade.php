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
    .cards { display: table; width: 100%; margin-bottom: 12px; }
    .card { display: table-cell; width: 25%; padding: 6px; }
    .card-inner { border: 1px solid #e0e0e0; border-radius: 6px; padding: 8px 10px; text-align: center; }
    .card-label { font-size: 7.5px; color: #777; margin-bottom: 3px; }
    .card-value { font-size: 14px; font-weight: bold; }
    .card-currency { font-size: 7px; color: #aaa; margin-top: 2px; }
    .green { color: #16a34a; }
    .red { color: #dc2626; }
    .blue { color: #0F4C75; }
    .gray { color: #555; }
    section { margin-bottom: 12px; }
    section h2 { font-size: 10px; font-weight: bold; color: #0F4C75; border-bottom: 1px solid #d0e4f5; padding-bottom: 4px; margin-bottom: 6px; }
    table.data { width: 100%; border-collapse: collapse; font-size: 8px; direction: rtl; }
    table.data thead tr { background: #0F4C75; color: #fff; }
    table.data thead th { padding: 4px 6px; font-weight: bold; border: 1px solid #0a3a5e; text-align: right; }
    table.data tbody tr:nth-child(even) { background: #f4f8fc; }
    table.data tbody td { padding: 3px 6px; border: 1px solid #e0e0e0; text-align: right; }
    table.data tbody td.c { text-align: center; }
    .total-row td { font-weight: bold; background: #e8f0f7 !important; color: #0F4C75; }
    .cash-box { border: 1px solid #d0e4f5; border-radius: 6px; padding: 8px 12px; }
    .cash-row { display: table; width: 100%; border-bottom: 1px solid #eee; padding: 4px 0; }
    .cash-row:last-child { border-bottom: none; }
    .cash-label { display: table-cell; font-size: 8px; color: #555; }
    .cash-value { display: table-cell; text-align: left; font-weight: bold; font-size: 8px; }
    .cash-total { background: #e8f0f7; border-radius: 4px; padding: 6px 8px; margin-top: 6px; display: table; width: 100%; }
    .footer { margin-top: 10px; border-top: 1px solid #eee; padding-top: 4px; font-size: 7.5px; color: #aaa; text-align: right; }
</style>
</head>
<body>

<div class="header">
    @include('partials.pdf-logo')
    <h1>تقرير إغلاق اليوم</h1>
    <div class="sub">{{ \Carbon\Carbon::parse($date)->isoFormat('dddd، D MMMM Y') }}</div>
</div>

{{-- Summary Cards --}}
<div class="cards">
    <div class="card">
        <div class="card-inner">
            <div class="card-label">إجمالي الإيرادات</div>
            <div class="card-value green">{{ number_format($totalRevenue, 0) }}</div>
            <div class="card-currency">ر.ي</div>
        </div>
    </div>
    <div class="card">
        <div class="card-inner">
            <div class="card-label">إجمالي المصروفات</div>
            <div class="card-value red">{{ number_format($totalExpenses, 0) }}</div>
            <div class="card-currency">ر.ي</div>
        </div>
    </div>
    <div class="card">
        <div class="card-inner">
            <div class="card-label">صافي اليوم</div>
            <div class="card-value {{ $netDay >= 0 ? 'blue' : 'red' }}">{{ number_format($netDay, 0) }}</div>
            <div class="card-currency">ر.ي</div>
        </div>
    </div>
    <div class="card">
        <div class="card-inner">
            <div class="card-label">الدفعات / الحجوزات</div>
            <div class="card-value gray">{{ $paymentCount }}</div>
            <div class="card-currency">{{ $reservationCount }} حجز</div>
        </div>
    </div>
</div>

{{-- Revenue by Method + Expenses by Category --}}
<div style="display:table; width:100%; margin-bottom:12px;">
    <div style="display:table-cell; width:50%; padding-left:6px;">
        <section>
            <h2>الإيرادات حسب طريقة الدفع</h2>
            <table class="data">
                <thead><tr>
                    <th>الطريقة</th>
                    <th>عدد الدفعات</th>
                    <th>المبلغ (ر.ي)</th>
                </tr></thead>
                <tbody>
                    @forelse($revenueByMethod as $row)
                    <tr>
                        <td>{{ match($row->method){'cash'=>'نقداً','bank_transfer'=>'تحويل بنكي','pos'=>'POS',default=>$row->method} }}</td>
                        <td class="c">{{ $row->count }}</td>
                        <td style="font-weight:bold; color:#0F4C75;">{{ number_format($row->total, 0) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="c" style="color:#999; padding:8px;">لا توجد إيرادات</td></tr>
                    @endforelse
                    @if($revenueByMethod->isNotEmpty())
                    <tr class="total-row">
                        <td>الإجمالي</td>
                        <td class="c">{{ $revenueByMethod->sum('count') }}</td>
                        <td>{{ number_format($revenueByMethod->sum('total'), 0) }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </section>
    </div>
    <div style="display:table-cell; width:50%; padding-right:6px;">
        <section>
            <h2>المصروفات حسب الفئة</h2>
            <table class="data">
                <thead><tr>
                    <th>الفئة</th>
                    <th>العدد</th>
                    <th>المبلغ (ر.ي)</th>
                </tr></thead>
                <tbody>
                    @forelse($expensesByCategory as $row)
                    <tr>
                        <td>{{ \App\Models\Expense::categoryLabel($row->category) }}</td>
                        <td class="c">{{ $row->count }}</td>
                        <td style="font-weight:bold; color:#dc2626;">{{ number_format($row->total, 0) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="c" style="color:#999; padding:8px;">لا توجد مصروفات</td></tr>
                    @endforelse
                    @if($expensesByCategory->isNotEmpty())
                    <tr class="total-row">
                        <td>الإجمالي</td>
                        <td class="c">{{ $expensesByCategory->sum('count') }}</td>
                        <td>{{ number_format($expensesByCategory->sum('total'), 0) }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </section>
    </div>
</div>

{{-- Daily Shifts --}}
<section>
    <h2>ورديات اليوم</h2>
    <table class="data">
        <thead><tr>
            <th>الموظف</th>
            <th>البدء</th>
            <th>الإقفال</th>
            <th>مستلمات (ر.ي)</th>
            <th>سحبيات (ر.ي)</th>
            <th>الصافي (ر.ي)</th>
            <th>عجز/فائض</th>
            <th>الحالة</th>
        </tr></thead>
        <tbody>
            @forelse($dailyShifts as $shift)
            @php $net = $shift->net_balance_yer; @endphp
            <tr>
                <td style="font-weight:bold;">{{ $shift->user->name ?? '—' }}</td>
                <td class="c">{{ $shift->started_at?->format('H:i') ?? '—' }}</td>
                <td class="c">{{ $shift->closed_at?->format('H:i') ?? '—' }}</td>
                <td style="color:#16a34a; font-weight:bold;">{{ number_format($shift->total_received_yer, 0) }}</td>
                <td style="color:#dc2626;">{{ $shift->total_withdrawals_yer > 0 ? number_format($shift->total_withdrawals_yer, 0) : '—' }}</td>
                <td style="font-weight:bold; color:#0F4C75;">{{ number_format($net, 0) }}</td>
                <td style="color:{{ ($shift->shortfall ?? 0) < 0 ? '#dc2626' : '#aaa' }};">
                    {{ ($shift->shortfall ?? 0) != 0 ? number_format($shift->shortfall, 0) : '—' }}
                </td>
                <td class="c">
                    @if($shift->is_closed)
                    <span style="color:#166534;">مقفلة</span>
                    @else
                    <span style="color:#92400e;">مفتوحة</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="c" style="color:#999; padding:8px;">لا توجد ورديات لهذا اليوم</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

{{-- Cash Settlement --}}
<section>
    <h2>تسوية الكاش (ريال يمني)</h2>
    <div class="cash-box">
        <div class="cash-row">
            <span class="cash-label">إجمالي المستلمات نقداً</span>
            <span class="cash-value green">{{ number_format($cashRevenue, 0) }} ر.ي</span>
        </div>
        <div class="cash-row">
            <span class="cash-label">إجمالي المصروفات نقداً</span>
            <span class="cash-value red">{{ number_format($cashExpenses, 0) }} ر.ي</span>
        </div>
        <div class="cash-row">
            <span class="cash-label">السحبيات من الصناديق</span>
            <span class="cash-value red">{{ number_format($totalWithdrawals, 0) }} ر.ي</span>
        </div>
        <div class="cash-total">
            <span class="cash-label" style="font-weight:bold; font-size:9px;">الرصيد النقدي المتوقع</span>
            <span class="cash-value blue" style="font-size:13px;">{{ number_format($expectedCash, 0) }} ر.ي</span>
        </div>
    </div>
</section>

<div class="footer">طُبع في: {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
