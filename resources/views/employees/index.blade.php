@extends('layouts.app')
@section('title', 'الموظفون')
@section('page-title', 'إدارة الموظفين')

@section('content')
<div dir="rtl">

    <div class="ui-head">
        <h2 class="ui-head-title">الموظفون</h2>
        <p class="ui-head-meta"><b>{{ number_format($employees->total()) }}</b> موظفاً</p>

        <div class="ui-head-actions">
            <a href="{{ route('employees.statements') }}" class="ui-btn">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                كشف حساب الموظفين
            </a>
            @can('hr.create')
            <a href="{{ route('employees.create') }}" class="ui-btn ui-btn--primary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                إضافة موظف
            </a>
            @endcan
        </div>
    </div>

    <div class="ui-panel">
        <div class="ui-table-wrap">
            <table class="ui-table" dir="rtl">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>الوظيفة</th>
                        {{-- الراتب هو الرقم الذي يُمسح بالعين، فيقف في عمود محاذىً --}}
                        <th class="num">الراتب الإجمالي</th>
                        <th>رقم الهاتف</th>
                        <th>تاريخ التوظيف</th>
                        <th>الحالة</th>
                        @canany(['hr.edit','hr.delete'])
                        <th class="t-actions">إجراءات</th>
                        @endcanany
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr>
                        <td>
                            <span class="t-strong">{{ $employee->name }}</span>
                            <span class="t-sub">
                                <a href="{{ route('employees.statement', $employee) }}"
                                   style="color:var(--ui-accent);font-weight:600;">كشف الحساب</a>
                                ·
                                <a href="{{ route('employees.withdrawals', $employee) }}"
                                   style="color:var(--ui-accent);">كشف المسحوبات</a>
                                ·
                                <a href="{{ route('employees.deductions', $employee) }}"
                                   style="color:var(--ui-neg);">الخصومات</a>
                            </span>
                        </td>
                        <td style="color:var(--ui-ink-2);">{{ $employee->position }}</td>
                        <td class="num">
                            <span class="t-strong">{{ number_format($employee->total_salary, 0) }}</span>
                            <span class="t-sub">
                                أساسي {{ number_format((float) $employee->base_salary, 0) }}
                                @if((float) $employee->food_allowance > 0)
                                + صرفية {{ number_format((float) $employee->food_allowance, 0) }}
                                @endif
                            </span>
                        </td>
                        <td style="color:var(--ui-ink-2);">{{ $employee->phone ?? '—' }}</td>
                        <td style="color:var(--ui-ink-2);">{{ $employee->hire_date->format('d/m/Y') }}</td>
                        <td>
                            @if($employee->is_active)
                            <span class="ui-chip ui-chip--ok">نشط</span>
                            @else
                            <span class="ui-chip ui-chip--bad">غير نشط</span>
                            @endif
                        </td>
                        @canany(['hr.edit','hr.delete'])
                        <td class="t-actions">
                            @can('hr.edit')
                            <a href="{{ route('employees.edit', $employee) }}" class="ui-btn ui-btn--sm">تعديل</a>
                            @endcan
                            @can('hr.delete')
                            <form method="POST" action="{{ route('employees.destroy', $employee) }}"
                                  style="display:inline;"
                                  onsubmit="return confirm('حذف الموظف {{ $employee->name }}؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ui-btn ui-btn--sm ui-btn--danger">حذف</button>
                            </form>
                            @endcan
                        </td>
                        @endcanany
                    </tr>
                    @empty
                    <tr><td colspan="7" style="padding:0;">
                        <x-empty-state
                            icon="👥"
                            title="لا يوجد موظفون"
                            message="ابدأ بإضافة موظف جديد لفريقك"
                            action_text="إضافة موظف"
                            action_url="{{ route('employees.create') }}"
                        />
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($employees->hasPages())
        <div class="ui-actions">
            <x-pagination-info :items="$employees" />
            <div style="margin-inline-start:auto;">{{ $employees->links() }}</div>
        </div>
        @endif
    </div>

</div>
@endsection
