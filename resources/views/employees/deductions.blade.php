@extends('layouts.app')
@section('title', 'خصومات راتب الموظف')
@section('page-title', 'خصومات راتب: ' . $employee->name)

@section('content')
<div dir="rtl" class="max-w-5xl mx-auto space-y-6">

    {{-- بطاقة الملخص --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between flex-wrap gap-3 mb-5">
            <div>
                <h2 class="text-lg font-bold text-gray-800">{{ $employee->name }}</h2>
                <p class="text-sm text-gray-500">{{ $employee->position }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('employees.statement', $employee) }}" class="text-sm text-blue-600 hover:underline">كشف الحساب</a>
                <a href="{{ route('employees.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← رجوع للموظفين</a>
            </div>
        </div>

        {{-- فلتر الشهر/السنة --}}
        <form method="GET" class="flex flex-wrap items-end gap-3 mb-6">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">الشهر</label>
                <select name="month" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    @foreach(range(1,12) as $m)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ \App\Models\Salary::monthName($m) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">السنة</label>
                <select name="year" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    @foreach(range(now()->year, now()->year - 3, -1) as $y)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm font-semibold" style="background:#0F4C75;">عرض</button>
        </form>

        @php $netAfter = $baseSalary - $monthTotal; @endphp
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-xs text-gray-500 mb-1">الراتب الأساسي</p>
                <p class="text-lg font-bold text-gray-800">{{ number_format($baseSalary, 0) }} <span class="text-xs font-normal">ر.ي</span></p>
            </div>
            <div class="bg-red-50 rounded-lg p-4 border border-red-100">
                <p class="text-xs text-red-700 mb-1">خصومات {{ \App\Models\Salary::monthName($month) }} {{ $year }}</p>
                <p class="text-lg font-bold text-red-800">
                    {{ number_format($monthTotal, 0) }} <span class="text-xs font-normal">ر.ي</span>
                    <span class="text-xs font-normal">({{ $deductions->count() }} خصم)</span>
                </p>
            </div>
            <div class="rounded-lg p-4 border {{ $netAfter < 0 ? 'bg-red-50 border-red-200' : 'bg-emerald-50 border-emerald-100' }}">
                <p class="text-xs mb-1 {{ $netAfter < 0 ? 'text-red-700' : 'text-emerald-700' }}">الراتب بعد هذه الخصومات</p>
                <p class="text-lg font-bold {{ $netAfter < 0 ? 'text-red-800' : 'text-emerald-800' }}">
                    {{ number_format($netAfter, 0) }} <span class="text-xs font-normal">ر.ي</span>
                </p>
                @if($netAfter < 0)
                {{-- الخصم تجاوز الراتب: الفارق يبقى ديناً على الموظف لا يُصفَّر --}}
                <p class="text-[11px] text-red-600 mt-1">الخصومات تجاوزت الراتب — الفارق دَين على الموظف</p>
                @endif
            </div>
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
                <p class="text-xs text-blue-700 mb-1">إجمالي الخصومات (كل الفترات)</p>
                <p class="text-lg font-bold text-blue-800">{{ number_format($allTimeTotal, 0) }} <span class="text-xs font-normal">ر.ي</span></p>
            </div>
        </div>

        @if($salary)
        <div class="mt-4 text-sm rounded-lg p-3 border {{ $salary->status === 'paid' ? 'bg-amber-50 border-amber-100 text-amber-700' : 'bg-gray-50 border-gray-100 text-gray-600' }}">
            @if($salary->status === 'paid')
            قسيمة راتب {{ \App\Models\Salary::monthName($month) }} {{ $year }} <strong>مدفوعة</strong> —
            أي خصم يُسجَّل الآن لن يُضاف إليها (أرقامها صُرفت فعلاً)، فسجّله على الشهر الذي سيُخصم فيه.
            @else
            قسيمة راتب {{ \App\Models\Salary::monthName($month) }} {{ $year }} مُنشأة —
            الخصومات المسجَّلة فيها: <strong>{{ number_format($salary->recorded_deductions, 0) }} ر.ي</strong>،
            وصافي الراتب <strong class="{{ $salary->net_salary < 0 ? 'text-red-700' : '' }}">{{ number_format($salary->net_salary, 0) }} ر.ي</strong>
            (تُحدَّث تلقائياً مع كل خصم).
            @endif
        </div>
        @else
        <div class="mt-4 text-xs bg-blue-50 border border-blue-100 rounded-lg p-3 text-blue-700">
            لم تُنشأ قسيمة راتب لهذا الشهر بعد — عند إنشائها ستُخصم هذه المبالغ منها تلقائياً.
        </div>
        @endif
    </div>

    {{-- تسجيل خصم جديد --}}
    @can('hr.edit')
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800 text-sm">تسجيل خصم جديد</h3>
            <p class="text-xs text-gray-400 mt-0.5">يُخصم من راتب الشهر الذي يقع فيه تاريخ الخصم</p>
        </div>

        @if($errors->any())
        <div class="mx-6 mt-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
            <ul class="list-disc pr-5 space-y-0.5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('employees.deductions.store', $employee) }}" class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">المبلغ *</label>
                <input type="number" name="amount" value="{{ old('amount') }}" step="0.01" min="0.01" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-red-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">السبب *</label>
                <select name="reason" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-red-400">
                    @foreach(\App\Models\SalaryDeduction::REASONS as $key => $label)
                    <option value="{{ $key }}" {{ old('reason') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">التاريخ *</label>
                <input type="date" name="deduction_date" value="{{ old('deduction_date', today()->toDateString()) }}"
                       max="{{ today()->toDateString() }}" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-red-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">البيان</label>
                <input type="text" name="description" value="{{ old('description') }}" placeholder="تفصيل الخصم (اختياري)"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-red-400">
            </div>
            <div class="md:col-span-4">
                <button type="submit" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold transition">
                    تسجيل الخصم
                </button>
            </div>
        </form>
    </div>
    @endcan

    {{-- سجل الخصومات --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800 text-sm">سجل الخصومات — {{ \App\Models\Salary::monthName($month) }} {{ $year }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">#</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">السبب</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">البيان</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">سجّله</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($deductions as $i => $d)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $d->deduction_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 font-bold text-red-700">{{ number_format($d->amount, 0) }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $d->reason_label }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $d->description ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $d->createdBy?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-left">
                            @can('hr.delete')
                            <form method="POST" action="{{ route('employees.deductions.destroy', [$employee, $d]) }}"
                                  onsubmit="return confirm('حذف هذا الخصم وإعادة احتساب قسيمة الشهر؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-600 hover:text-red-800 hover:underline">حذف</button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">لا توجد خصومات على هذا الموظف في هذا الشهر</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($deductions->isNotEmpty())
                <tfoot class="bg-gray-50 font-bold">
                    <tr>
                        <td colspan="2" class="px-4 py-3 text-gray-700">الإجمالي</td>
                        <td class="px-4 py-3 text-red-800">{{ number_format($monthTotal, 0) }}</td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
@endsection
