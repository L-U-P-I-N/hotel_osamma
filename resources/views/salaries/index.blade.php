@extends('layouts.app')
@section('title', 'الرواتب')
@section('page-title', 'معالجة الرواتب الشهرية')

@section('content')
<div dir="rtl">

    <div class="ui-head">
        <h2 class="ui-head-title">رواتب {{ \App\Models\Salary::monthName($month) }} {{ $year }}</h2>
        <p class="ui-head-meta">
            <b>{{ $salaries->count() }}</b> قسيمة بصافٍ قدره
            <b>{{ number_format($salaries->sum('net_salary'), 0) }}</b> ر.ي
        </p>
        @can('hr.create')
        <div class="ui-head-actions">
            <a href="{{ route('salaries.create') }}" class="ui-btn ui-btn--primary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                إنشاء قسيمة راتب
            </a>
        </div>
        @endcan
    </div>

    <form method="GET" class="ui-bar">
        <label class="inline" for="sal-month">الشهر</label>
        <select id="sal-month" name="month" onchange="this.form.submit()">
            @foreach(range(1,12) as $m)
            <option value="{{ $m }}" @selected($month == $m)>{{ \App\Models\Salary::monthName($m) }}</option>
            @endforeach
        </select>
        <label class="inline" for="sal-year">السنة</label>
        <select id="sal-year" name="year" onchange="this.form.submit()">
            @foreach(range(now()->year, now()->year - 3, -1) as $y)
            <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
            @endforeach
        </select>
        <button type="submit" class="ui-btn">عرض</button>
    </form>

    <div class="ui-panel">
        <div class="ui-table-wrap">
            <table class="ui-table" dir="rtl">
                <thead>
                    <tr>
                        <th>الموظف</th>
                        <th class="num">الراتب الأساسي</th>
                        <th class="num">المكافآت</th>
                        <th class="num">إجمالي الخصومات</th>
                        <th class="num">الصافي</th>
                        <th>الحالة</th>
                        @can('hr.edit')
                        <th class="t-actions">إجراءات</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse($salaries as $salary)
                    <tr>
                        <td class="t-strong">{{ $salary->employee->name }}</td>
                        <td class="num num--muted">{{ number_format($salary->base_salary, 0) }}</td>
                        <td class="num num--pos">{{ number_format($salary->bonuses, 0) }}</td>
                        <td class="num num--neg"
                            title="خصومات يدوية: {{ number_format($salary->deductions, 0) }} — خصومات مسجَّلة: {{ number_format($salary->recorded_deductions, 0) }} — مسحوبات: {{ number_format($salary->withdrawals_deduction, 0) }} — غياب/إجازة: {{ number_format($salary->attendance_deduction, 0) }}">
                            {{ number_format($salary->total_deductions, 0) }}
                        </td>
                        <td class="num t-strong" style="color:var(--ui-accent);">{{ number_format($salary->net_salary, 0) }}</td>
                        <td>
                            @if($salary->status === 'paid')
                            <span class="ui-chip ui-chip--ok">مدفوعة</span>
                            @else
                            <span class="ui-chip ui-chip--warn">مسودة</span>
                            @endif
                        </td>
                        @can('hr.edit')
                        <td class="t-actions">
                            <a href="{{ route('salaries.pdf', $salary) }}" target="_blank" class="ui-btn ui-btn--sm">PDF</a>

                            @if($salary->status === 'draft')
                            <a href="{{ route('salaries.edit', $salary) }}" class="ui-btn ui-btn--sm">تعديل</a>

                            {{-- الصرف يسأل عن الوعاء الذي خرج منه المال قبل أن يُسجَّل --}}
                            <span x-data="{ open: false }" style="position:relative;display:inline-block;">
                                <button type="button" class="ui-btn ui-btn--sm ui-btn--primary" @click="open = !open">مدفوعة</button>
                                <form x-show="open" x-cloak @click.outside="open = false"
                                      method="POST" action="{{ route('salaries.markPaid', $salary) }}"
                                      x-data="{ method: 'cash' }"
                                      class="ui-panel"
                                      style="position:absolute;inset-inline-start:0;top:calc(100% + .375rem);width:15rem;z-index:30;
                                             margin:0;padding:.625rem;text-align:start;box-shadow:0 8px 24px rgba(15,23,42,.14);">
                                    @csrf @method('PATCH')
                                    <p style="font-size:.6875rem;font-weight:700;color:var(--ui-ink);margin-bottom:.375rem;">
                                        من أين صُرف الراتب؟
                                    </p>
                                    <select name="payment_method" x-model="method" class="ui-input" style="height:2rem;margin-bottom:.375rem;">
                                        <option value="cash">نقداً</option>
                                        <option value="bank_transfer">تحويل بنكي</option>
                                    </select>
                                    <select name="payment_account_id" class="ui-input" style="height:2rem;">
                                        <template x-if="method === 'cash'">
                                            <optgroup label="نقداً">
                                                @foreach(\App\Models\PaymentAccount::active()->whereIn('type', ['shift_cash','safe'])->ordered()->get() as $__acc)
                                                <option value="{{ $__acc->id }}">{{ $__acc->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        </template>
                                        <template x-if="method === 'bank_transfer'">
                                            <optgroup label="بنكياً">
                                                @foreach(\App\Models\PaymentAccount::active()->where('type','bank')->ordered()->get() as $__acc)
                                                <option value="{{ $__acc->id }}">{{ $__acc->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        </template>
                                    </select>
                                    <button type="submit" class="ui-btn ui-btn--primary" style="width:100%;margin-top:.5rem;">تأكيد الصرف</button>
                                </form>
                            </span>

                            @can('hr.delete')
                            <form method="POST" action="{{ route('salaries.destroy', $salary) }}" style="display:inline;"
                                  onsubmit="return confirm('حذف قسيمة الراتب هذه؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ui-btn ui-btn--sm ui-btn--danger">حذف</button>
                            </form>
                            @endcan
                            @endif
                        </td>
                        @endcan
                    </tr>
                    @empty
                    <tr><td colspan="7"><div class="ui-empty">لا توجد رواتب لهذا الشهر</div></td></tr>
                    @endforelse
                </tbody>

                @if($salaries->isNotEmpty())
                <tfoot>
                    <tr>
                        <td>الإجمالي</td>
                        <td class="num">{{ number_format($salaries->sum('base_salary'), 0) }}</td>
                        <td class="num num--pos">{{ number_format($salaries->sum('bonuses'), 0) }}</td>
                        <td class="num num--neg">{{ number_format($salaries->sum('total_deductions'), 0) }}</td>
                        <td class="num" style="color:var(--ui-accent);">{{ number_format($salaries->sum('net_salary'), 0) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
@endsection
