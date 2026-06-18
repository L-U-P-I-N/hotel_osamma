@extends('layouts.app')
@section('title', 'طلبات الإجازة')
@section('page-title', 'إدارة طلبات الإجازة')

@section('content')
<div dir="rtl">

<!-- Header -->
<div class="flex items-center justify-between mb-5">
    <p class="text-sm text-gray-500">إجمالي الطلبات: {{ $leaves->total() }}</p>
    @can('hr.manage')
    <a href="{{ route('leaves.create') }}" class="flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        طلب إجازة جديد
    </a>
    @endcan
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">الحالة</label>
            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                <option value="">جميع الحالات</option>
                <option value="pending" {{ request('status')=='pending'?'selected':'' }}>قيد المراجعة</option>
                <option value="approved" {{ request('status')=='approved'?'selected':'' }}>موافق عليها</option>
                <option value="rejected" {{ request('status')=='rejected'?'selected':'' }}>مرفوضة</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">الموظف</label>
            <select name="employee_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                <option value="">جميع الموظفين</option>
                @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">تصفية</button>
        <a href="{{ route('leaves.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm hover:bg-gray-200 transition">إعادة تعيين</a>
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100" x-data="{ reviewModal: false, leaveId: null, reviewStatus: 'approved' }">
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="rtl">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الموظف</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">نوع الإجازة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">من</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إلى</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">عدد الأيام</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                    @can('hr.manage')
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إجراءات</th>
                    @endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($leaves as $leave)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-semibold text-gray-800">{{ $leave->employee->name }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            {{ \App\Models\Leave::typeLabel($leave->type) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $leave->start_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $leave->end_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-center font-medium text-gray-700">{{ $leave->days_count }}</td>
                    <td class="px-4 py-3">
                        @php
                            $cls = match($leave->status) {
                                'pending'  => 'bg-yellow-100 text-yellow-800',
                                'approved' => 'bg-green-100 text-green-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                default    => 'bg-gray-100 text-gray-800',
                            };
                        @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $cls }}">
                            {{ \App\Models\Leave::statusLabel($leave->status) }}
                        </span>
                    </td>
                    @can('hr.manage')
                    <td class="px-4 py-3">
                        @if($leave->status === 'pending')
                        <button @click="leaveId={{ $leave->id }}; reviewStatus='approved'; reviewModal=true"
                                class="text-xs px-3 py-1.5 rounded-lg text-white transition mr-1" style="background:#16a34a;">
                            موافقة
                        </button>
                        <button @click="leaveId={{ $leave->id }}; reviewStatus='rejected'; reviewModal=true"
                                class="text-xs px-3 py-1.5 rounded-lg text-white transition bg-red-600">
                            رفض
                        </button>
                        @else
                        <span class="text-xs text-gray-400">{{ $leave->reviewer?->name ?? '-' }}</span>
                        @endif
                    </td>
                    @endcan
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">لا توجد طلبات إجازة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($leaves->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $leaves->links() }}
    </div>
    @endif

    <!-- Review Modal -->
    @can('hr.manage')
    <div x-show="reviewModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="reviewModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
            <h3 class="font-bold text-gray-800 mb-4">مراجعة الإجازة</h3>
            <template x-for="leave in [leaveId]" :key="leave">
                <form method="POST" :action="`/leaves/${leaveId}`" class="space-y-4">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" :value="reviewStatus">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">ملاحظات</label>
                        <textarea name="review_notes" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none resize-none"></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="flex-1 py-2.5 text-white rounded-lg text-sm font-semibold" :style="reviewStatus==='approved' ? 'background:#16a34a' : 'background:#dc2626'">
                            <span x-text="reviewStatus==='approved' ? 'تأكيد الموافقة' : 'تأكيد الرفض'"></span>
                        </button>
                        <button type="button" @click="reviewModal=false" class="flex-1 border border-gray-300 text-gray-700 py-2.5 rounded-lg text-sm hover:bg-gray-50">إلغاء</button>
                    </div>
                </form>
            </template>
        </div>
    </div>
    @endcan
</div>

</div>
@endsection
