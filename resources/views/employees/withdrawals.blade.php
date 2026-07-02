@extends('layouts.app')
@section('title', 'كشف مسحوبات الموظف')
@section('page-title', 'كشف مسحوبات: ' . $employee->name)

@section('content')
<div dir="rtl" class="max-w-4xl mx-auto space-y-6">

    {{-- بطاقة الملخص --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-lg font-bold text-gray-800">{{ $employee->name }}</h2>
                <p class="text-sm text-gray-500">{{ $employee->position }}</p>
            </div>
            <a href="{{ route('employees.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← رجوع للموظفين</a>
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

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-xs text-gray-500 mb-1">الراتب الأساسي</p>
                <p class="text-lg font-bold text-gray-800">{{ number_format($baseSalary, 0) }} <span class="text-xs font-normal">ر.ي</span></p>
            </div>
            <div class="bg-amber-50 rounded-lg p-4 border border-amber-100">
                <p class="text-xs text-amber-700 mb-1">مسحوبات {{ \App\Models\Salary::monthName($month) }} {{ $year }}</p>
                <p class="text-lg font-bold text-amber-800">{{ number_format($monthTotal, 0) }} <span class="text-xs font-normal">ر.ي</span> <span class="text-xs font-normal">({{ $withdrawals->count() }} عملية)</span></p>
            </div>
            <div class="rounded-lg p-4 border {{ $remainingSalary < 0 ? 'bg-red-50 border-red-100' : 'bg-emerald-50 border-emerald-100' }}">
                <p class="text-xs mb-1 {{ $remainingSalary < 0 ? 'text-red-700' : 'text-emerald-700' }}">المتبقي من الراتب بعد المسحوبات</p>
                <p class="text-lg font-bold {{ $remainingSalary < 0 ? 'text-red-800' : 'text-emerald-800' }}">{{ number_format($remainingSalary, 0) }} <span class="text-xs font-normal">ر.ي</span></p>
            </div>
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
                <p class="text-xs text-blue-700 mb-1">إجمالي المسحوبات (كل الفترات)</p>
                <p class="text-lg font-bold text-blue-800">{{ number_format($allTimeTotal, 0) }} <span class="text-xs font-normal">ر.ي</span></p>
            </div>
        </div>

        @if($salary)
        <div class="mt-4 text-sm bg-gray-50 border border-gray-100 rounded-lg p-3 text-gray-600">
            قسيمة راتب {{ \App\Models\Salary::monthName($month) }} {{ $year }} مُنشأة:
            خصومات مسجّلة <strong>{{ number_format($salary->deductions, 0) }} ر.ي</strong>
            — صافي الراتب <strong>{{ number_format($salary->net_salary, 0) }} ر.ي</strong>
            ({{ $salary->status === 'paid' ? 'مدفوع' : 'غير مدفوع' }})
        </div>
        @else
        <div class="mt-4 text-xs bg-amber-50 border border-amber-100 rounded-lg p-3 text-amber-700">
            لم تُنشأ قسيمة راتب لهذا الشهر بعد — عند إنشائها ستُخصم المسحوبات تلقائياً من الراتب.
        </div>
        @endif
    </div>

    {{-- جدول المسحوبات --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800 text-sm">تفاصيل المسحوبات — {{ \App\Models\Salary::monthName($month) }} {{ $year }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">#</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">وقت التسجيل</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المبلغ (ر.ي)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الفئة</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الوصف</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">سجّله</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($withdrawals as $i => $w)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $w->expense_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $w->created_at?->format('h:i A') ?? '—' }}</td>
                        <td class="px-4 py-3 font-bold text-amber-700">{{ number_format($w->amount, 0) }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ \App\Models\Expense::categoryLabel($w->category) }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $w->description ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $w->paidBy?->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">لا توجد مسحوبات لهذا الموظف في هذا الشهر</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($withdrawals->isNotEmpty())
                <tfoot class="bg-gray-50 font-bold">
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-gray-700">الإجمالي</td>
                        <td class="px-4 py-3 text-amber-800">{{ number_format($monthTotal, 0) }}</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
@endsection
