<!DOCTYPE html>
<html dir="rtl" lang="ar">
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
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'NotoNaskhArabic',sans-serif; font-size:10pt; color:#1a1a1a; background:#fff; direction:rtl; padding:14mm 12mm; }

/* Header */
.header { text-align:center; border-bottom:2px solid #0F4C75; padding-bottom:8px; margin-bottom:12px; }
.header h1 { font-size:16pt; font-weight:bold; color:#0F4C75; }
.header .sub { font-size:9pt; color:#555; margin-top:3px; }

/* Status bar */
.status-bar { display:table; width:100%; border-collapse:collapse; margin-bottom:12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:4px; padding:8px 12px; }
.status-bar .res-num { font-size:14pt; font-weight:bold; color:#0F4C75; }
.status-bar .badges { font-size:9pt; margin-top:4px; }
.badge { display:inline-block; padding:1px 8px; border-radius:10px; font-size:8pt; font-weight:bold; margin-left:4px; }
.badge-checked_in  { background:#dcfce7; color:#166534; }
.badge-checked_out { background:#f1f5f9; color:#475569; }
.badge-paid    { background:#dcfce7; color:#166534; }
.badge-partial { background:#fef9c3; color:#92400e; }
.badge-unpaid  { background:#fee2e2; color:#991b1b; }
.badge-deferred{ background:#f3e8ff; color:#6b21a8; }

/* Summary ribbon */
.ribbon { display:table; width:100%; border-collapse:separate; border-spacing:6px; margin-bottom:12px; }
.ribbon-cell { display:table-cell; border:1px solid #e2e8f0; border-radius:4px; padding:8px; text-align:center; width:25%; }
.ribbon-cell .lbl { font-size:7.5pt; color:#6b7280; margin-bottom:3px; }
.ribbon-cell .val { font-size:14pt; font-weight:bold; color:#0F4C75; }
.ribbon-cell .sub { font-size:7pt; color:#9ca3af; margin-top:2px; }

/* Section card */
.card { border:1px solid #e5e7eb; border-radius:4px; margin-bottom:12px; overflow:hidden; }
.card-header { background:#0F4C75; color:#fff; padding:6px 12px; font-size:10pt; font-weight:bold; }
.card-body { padding:10px 12px; }

/* Info grid */
.info-grid { display:table; width:100%; border-collapse:collapse; }
.info-row { display:table-row; }
.info-lbl { display:table-cell; width:18%; font-size:8pt; color:#6b7280; padding:4px 6px 4px 0; vertical-align:top; white-space:nowrap; }
.info-val { display:table-cell; width:32%; font-size:9.5pt; color:#1a1a1a; padding:4px 12px 4px 0; vertical-align:top; }

/* Tables */
table.data { width:100%; border-collapse:collapse; font-size:9pt; }
table.data thead tr { background:#0F4C75; color:#fff; }
table.data thead th { padding:5px 8px; text-align:right; font-size:8.5pt; border:1px solid #0a3a5e; }
table.data tbody td { padding:4px 8px; border:1px solid #e5e7eb; vertical-align:top; }
table.data tbody tr:nth-child(even) td { background:#f8fafc; }

/* Financial */
.fin-row { display:table; width:100%; border-collapse:collapse; padding:4px 0; }
.fin-lbl { display:table-cell; color:#6b7280; font-size:9pt; }
.fin-val { display:table-cell; text-align:left; font-size:9pt; font-weight:bold; }
.fin-total { border-top:1px solid #e5e7eb; margin-top:4px; padding-top:6px; }
.fin-total .fin-lbl { color:#1a1a1a; font-weight:bold; }
.fin-total .fin-val { font-size:11pt; color:#0F4C75; }
.balance-row { background:#f0f9ff; border:1px solid #bae6fd; border-radius:4px; padding:6px 10px; margin-top:6px; }

/* Notes */
.notes-box { background:#fffbeb; border:1px solid #fde68a; border-radius:4px; padding:8px 10px; font-size:9pt; color:#92400e; }

/* Companion */
.companion-header { background:#f5f3ff; border-bottom:1px solid #ddd6fe; padding:5px 10px; font-size:9pt; font-weight:bold; color:#5b21b6; }

/* Footer */
.footer { margin-top:14px; border-top:1px solid #e5e7eb; padding-top:8px; text-align:center; font-size:8pt; color:#9ca3af; }
</style>
</head>
<body>
@php
    $pricePerNight = $reservation->nights > 0 ? round($reservation->total_amount / $reservation->nights, 0) : 0;
    $balance = (float)$reservation->total_amount - (float)$reservation->paid_amount;
    $psLabels = ['paid'=>'مكتمل الدفع','partial'=>'دفع جزئي','unpaid'=>'غير مدفوع','deferred'=>'آجل'];
    $stLabels = ['checked_in'=>'مقيم حالياً','checked_out'=>'غادر'];
@endphp

{{-- Header --}}
<div class="header">
    <h1>فندق السعودي</h1>
    <div class="sub">تفاصيل الحجز — رقم #{{ $reservation->id }}</div>
</div>

{{-- Status bar --}}
<div class="status-bar">
    <div class="res-num">حجز #{{ str_pad($reservation->id, 6, '0', STR_PAD_LEFT) }}</div>
    <div class="badges">
        <span class="badge badge-{{ $reservation->status }}">{{ $stLabels[$reservation->status] ?? $reservation->status }}</span>
        <span class="badge badge-{{ $reservation->payment_status }}">{{ $psLabels[$reservation->payment_status] ?? $reservation->payment_status }}</span>
    </div>
</div>

{{-- Summary ribbon --}}
<div class="ribbon">
    <div class="ribbon-cell">
        <div class="lbl">الغرفة</div>
        <div class="val">{{ $reservation->display_room_number }}</div>
        <div class="sub">{{ $reservation->room?->roomType?->name ?? '' }}</div>
    </div>
    <div class="ribbon-cell">
        <div class="lbl">عدد الليالي</div>
        <div class="val">{{ $reservation->nights }}</div>
        <div class="sub">{{ number_format($pricePerNight, 0) }} ر.ي / ليلة</div>
    </div>
    <div class="ribbon-cell">
        <div class="lbl">إجمالي الحجز</div>
        <div class="val">{{ number_format($reservation->total_amount, 0) }}</div>
        <div class="sub">{{ $reservation->currency_symbol }}</div>
    </div>
    <div class="ribbon-cell">
        <div class="lbl">{{ $balance > 0 ? 'المتبقي' : 'الرصيد' }}</div>
        <div class="val" style="color:{{ $balance > 0 ? '#dc2626' : '#16a34a' }};">{{ number_format(abs($balance), 0) }}</div>
        <div class="sub">{{ $reservation->currency_symbol }}</div>
    </div>
</div>

{{-- Guest Info --}}
<div class="card">
    <div class="card-header">بيانات النزيل الرئيسي</div>
    <div class="card-body">
        <div class="info-grid">
            <div class="info-row">
                <span class="info-lbl">الاسم الكامل</span>
                <span class="info-val" style="font-weight:bold;">{{ $reservation->guest?->full_name ?? '—' }}</span>
                <span class="info-lbl">الجنسية</span>
                <span class="info-val">{{ $reservation->guest?->nationality ?: '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">المهنة</span>
                <span class="info-val">{{ $reservation->guest?->occupation ?: '—' }}</span>
                <span class="info-lbl">نوع الهوية</span>
                <span class="info-val">{{ $reservation->guest?->getIdTypeLabel() ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">رقم الهوية</span>
                <span class="info-val">{{ $reservation->guest?->id_number ?? '—' }}</span>
                <span class="info-lbl">رقم الجوال</span>
                <span class="info-val">{{ $reservation->guest?->phone ?: '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">جهة الإصدار</span>
                <span class="info-val">{{ $reservation->guest?->id_issuer ?: '—' }}</span>
                <span class="info-lbl">تاريخ الإصدار</span>
                <span class="info-val">{{ $reservation->guest?->id_issue_date?->format('d/m/Y') ?: '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">جهة القدوم</span>
                <span class="info-val">{{ $reservation->origin ?: '—' }}</span>
                <span class="info-lbl">الغرض</span>
                <span class="info-val">{{ $reservation->purpose ?: '—' }}</span>
            </div>
        </div>
        @if($reservation->notes)
        <div class="notes-box" style="margin-top:8px;">
            <strong style="font-size:8pt;">ملاحظات:</strong> {{ $reservation->notes }}
        </div>
        @endif
    </div>
</div>

{{-- Companions --}}
@if($reservation->companions->count() > 0)
<div class="card">
    <div class="card-header">المرافقون ({{ $reservation->companions->count() }})</div>
    @foreach($reservation->companions as $idx => $c)
    @if($idx > 0)<div style="border-top:1px solid #e5e7eb;"></div>@endif
    <div style="padding:8px 12px;">
        <div style="font-weight:bold;font-size:9.5pt;margin-bottom:6px;color:#5b21b6;">
            {{ $idx+1 }}. {{ $c->full_name }}
            <span style="font-size:8pt;font-weight:normal;color:#7c3aed;"> — {{ $c->getRelationshipLabel() }}</span>
        </div>
        <div class="info-grid">
            <div class="info-row">
                <span class="info-lbl">الجنسية</span>
                <span class="info-val">{{ $c->nationality ?: '—' }}</span>
                <span class="info-lbl">نوع الهوية</span>
                <span class="info-val">{{ $c->getIdTypeLabel() ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">رقم الهوية</span>
                <span class="info-val">{{ $c->id_number ?? '—' }}</span>
                <span class="info-lbl">جهة الإصدار</span>
                <span class="info-val">{{ $c->id_issuer ?: '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-lbl">تاريخ الإصدار</span>
                <span class="info-val">{{ $c->id_issue_date?->format('d/m/Y') ?: '—' }}</span>
                <span class="info-lbl"></span><span class="info-val"></span>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Reservation Dates + Financial side by side --}}
<div style="display:table;width:100%;border-collapse:separate;border-spacing:8px;margin-bottom:12px;">
    {{-- Dates --}}
    <div style="display:table-cell;width:50%;vertical-align:top;">
        <div class="card" style="margin-bottom:0;">
            <div class="card-header">تواريخ الحجز</div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-row">
                        <span class="info-lbl">تاريخ الدخول</span>
                        <span class="info-val" style="font-weight:bold;color:#16a34a;">
                            {{ $reservation->check_in_date?->format('d/m/Y') ?? '—' }}
                            @if($reservation->check_in_time)
                            <span style="font-size:8pt;color:#6b7280;font-weight:normal;"> {{ $reservation->check_in_time }}</span>
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-lbl">تاريخ الخروج</span>
                        <span class="info-val" style="font-weight:bold;color:#dc2626;">{{ $reservation->check_out_date?->format('d/m/Y') ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-lbl">سُجِّل بواسطة</span>
                        <span class="info-val">{{ $reservation->createdBy?->name ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-lbl">تاريخ الإنشاء</span>
                        <span class="info-val">{{ $reservation->created_at?->format('d/m/Y H:i') ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Financial --}}
    <div style="display:table-cell;width:50%;vertical-align:top;">
        <div class="card" style="margin-bottom:0;">
            <div class="card-header">الملخص المالي</div>
            <div class="card-body">
                <div class="fin-row"><span class="fin-lbl">سعر الليلة</span><span class="fin-val">{{ number_format($pricePerNight, 0) }} {{ $reservation->currency_symbol }}</span></div>
                <div class="fin-row"><span class="fin-lbl">عدد الليالي</span><span class="fin-val">{{ $reservation->nights }}</span></div>
                @if($reservation->extraCharges->count() > 0)
                <div class="fin-row"><span class="fin-lbl">رسوم إضافية</span><span class="fin-val" style="color:#dc2626;">+{{ number_format($reservation->extraCharges->sum('amount'), 0) }} {{ $reservation->currency_symbol }}</span></div>
                @endif
                @if($reservation->discount_amount > 0)
                <div class="fin-row"><span class="fin-lbl">خصم</span><span class="fin-val" style="color:#dc2626;">-{{ number_format($reservation->discount_amount, 0) }} {{ $reservation->currency_symbol }}</span></div>
                @endif
                <div class="fin-total">
                    <div class="fin-row"><span class="fin-lbl">الإجمالي</span><span class="fin-val">{{ number_format($reservation->total_amount, 2) }} {{ $reservation->currency_symbol }}</span></div>
                    <div class="fin-row"><span class="fin-lbl">المدفوع</span><span class="fin-val" style="color:#16a34a;">{{ number_format($reservation->paid_amount, 2) }} {{ $reservation->currency_symbol }}</span></div>
                </div>
                <div class="balance-row">
                    <div class="fin-row">
                        <span class="fin-lbl" style="font-weight:bold;color:{{ $balance > 0 ? '#dc2626' : '#16a34a' }};">{{ $balance > 0 ? 'المتبقي' : 'مكتمل الدفع' }}</span>
                        <span class="fin-val" style="font-size:12pt;color:{{ $balance > 0 ? '#dc2626' : '#16a34a' }};">{{ number_format(abs($balance), 2) }} {{ $reservation->currency_symbol }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Extra Charges --}}
@if($reservation->extraCharges->count() > 0)
<div class="card">
    <div class="card-header">الرسوم الإضافية</div>
    <div class="card-body" style="padding:0;">
        <table class="data">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>النوع</th>
                    <th>الوصف</th>
                    <th>المبلغ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reservation->extraCharges as $charge)
                <tr>
                    <td>{{ $charge->charge_date->format('d/m/Y') }}</td>
                    <td>{{ $charge->type }}</td>
                    <td>{{ $charge->description ?: '—' }}</td>
                    <td style="color:#dc2626;font-weight:bold;">{{ number_format($charge->amount, 0) }} {{ $reservation->currency_symbol }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Payments History --}}
@if($reservation->payments->count() > 0)
<div class="card">
    <div class="card-header">سجل المدفوعات</div>
    <div class="card-body" style="padding:0;">
        <table class="data">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>المبلغ</th>
                    <th>الطريقة</th>
                    <th>سبب الدفع</th>
                    <th>ملاحظة</th>
                    <th>استلم بواسطة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reservation->payments as $p)
                @php
                    $tl = match($p->type) {
                        'reservation'   => ['label'=>'دفعة حجز',    'bg'=>'#dbeafe','color'=>'#1d4ed8'],
                        'renewal'       => ['label'=>'دفعة تجديد',  'bg'=>'#fef9c3','color'=>'#b45309'],
                        'compensation'  => ['label'=>'تعويض أضرار', 'bg'=>'#fee2e2','color'=>'#b91c1c'],
                        'extra_service' => ['label'=>'خدمة إضافية', 'bg'=>'#f3e8ff','color'=>'#6b21a8'],
                        default         => ['label'=>$p->type,       'bg'=>'#f3f4f6','color'=>'#374151'],
                    };
                    $ml = match($p->method) {
                        'cash'          => ['label'=>'نقدي',         'bg'=>'#dcfce7','color'=>'#166534'],
                        'pos'           => ['label'=>'POS',           'bg'=>'#dbeafe','color'=>'#1d4ed8'],
                        'bank_transfer' => ['label'=>'تحويل',        'bg'=>'#f3e8ff','color'=>'#6b21a8'],
                        default         => ['label'=>$p->method,      'bg'=>'#f3f4f6','color'=>'#374151'],
                    };
                @endphp
                <tr>
                    <td style="white-space:nowrap;">{{ $p->payment_date?->format('d/m/Y H:i') }}</td>
                    <td style="font-weight:bold;color:#16a34a;white-space:nowrap;">{{ number_format($p->amount, 2) }} <span style="font-size:7.5pt;font-weight:normal;color:#9ca3af;">{{ $reservation->currency_symbol }}</span></td>
                    <td><span style="padding:1px 7px;border-radius:10px;font-size:8pt;background:{{ $ml['bg'] }};color:{{ $ml['color'] }};">{{ $ml['label'] }}</span></td>
                    <td><span style="padding:1px 7px;border-radius:10px;font-size:8pt;background:{{ $tl['bg'] }};color:{{ $tl['color'] }};">{{ $tl['label'] }}</span></td>
                    <td style="font-size:8pt;color:#6b7280;">{{ $p->notes ?: '—' }}</td>
                    <td style="color:#374151;">{{ $p->receivedBy?->name ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Room Inspection --}}
@if($reservation->status === 'checked_out' && $reservation->roomInspections->count() > 0)
@php $insp = $reservation->roomInspections->first(); @endphp
<div class="card" style="border-color:{{ $insp->has_damage ? '#fca5a5' : '#86efac' }};">
    <div class="card-header" style="background:{{ $insp->has_damage ? '#dc2626' : '#16a34a' }};">
        تقرير فحص الغرفة — {{ $insp->inspection_date?->format('d/m/Y H:i') }}
    </div>
    <div class="card-body">
        @if($insp->has_damage)
        <div style="color:#dc2626;font-weight:bold;margin-bottom:6px;">تم تسجيل أضرار في الغرفة</div>
        @if($insp->damage_description)
        <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:4px;padding:8px;font-size:9pt;color:#991b1b;margin-bottom:6px;">{{ $insp->damage_description }}</div>
        @endif
        @if($insp->compensation_amount > 0)
        <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:4px;padding:6px 10px;">
            <span style="color:#9a3412;font-size:9pt;">مبلغ التعويض: </span>
            <span style="font-weight:bold;color:#c2410c;">{{ number_format($insp->compensation_amount, 0) }} {{ $reservation->currency_symbol }}</span>
            <span style="color:{{ $insp->compensation_status === 'paid' ? '#16a34a' : '#d97706' }};font-size:8.5pt;margin-right:8px;">
                — {{ $insp->compensation_status === 'paid' ? 'التعويض مدفوع' : 'التعويض معلق' }}
            </span>
        </div>
        @endif
        @else
        <div style="color:#16a34a;font-weight:bold;">الغرفة بحالة جيدة — لا توجد أضرار</div>
        @endif
    </div>
</div>
@endif

<div class="footer">
    طُبع في: {{ now()->format('d/m/Y H:i') }} — فندق السعودي | نظام إدارة الفندق
</div>

</body>
</html>
