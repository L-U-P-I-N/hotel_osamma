@extends('layouts.app')
@section('title', 'سجل المراجعة')
@section('page-title', 'سجل المراجعة')

@section('content')
<div class="space-y-5">

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">الموظف</label>
            <select name="user_id" onchange="this.form.submit()"
                    class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white min-w-[160px]">
                <option value="">جميع الموظفين</option>
                @foreach($users as $u)
                <option value="{{ $u->id }}" {{ request('user_id')==$u->id?'selected':'' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">نوع الحدث</label>
            <select name="action" onchange="this.form.submit()"
                    class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white min-w-[160px]">
                <option value="">جميع الأحداث</option>
                <option value="create" {{ request('action')=='create'?'selected':'' }}>إنشاء</option>
                <option value="update" {{ request('action')=='update'?'selected':'' }}>تحديث</option>
                <option value="delete" {{ request('action')=='delete'?'selected':'' }}>حذف</option>
                <option value="login" {{ request('action')=='login'?'selected':'' }}>دخول</option>
                <option value="logout" {{ request('action')=='logout'?'selected':'' }}>خروج</option>
                <option value="export" {{ request('action')=='export'?'selected':'' }}>تصدير</option>
                <option value="view_sensitive" {{ request('action')=='view_sensitive'?'selected':'' }}>بيانات حساسة</option>
            </select>
        </div>
        <button type="submit" class="sr-only">فلترة</button>
        @if(request()->hasAny(['user_id','action']))
        <a href="{{ route('audit.log') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-xs text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition self-end">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            مسح
        </a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">سجل الأحداث
            <span class="text-gray-400 font-normal text-sm mr-2">({{ $logs->total() }} حدث)</span>
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ والوقت</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الموظف</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحدث</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النموذج</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">عنوان IP</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($logs as $log)
                @php
                    $actionColors = ['create'=>'bg-green-100 text-green-800','update'=>'bg-blue-100 text-blue-800','delete'=>'bg-red-100 text-red-800','login'=>'bg-primary-100 text-primary-800','logout'=>'bg-gray-100 text-gray-800','export'=>'bg-yellow-100 text-yellow-800','view_sensitive'=>'bg-orange-100 text-orange-800'];
                    $actionLabels = ['create'=>'إنشاء','update'=>'تحديث','delete'=>'حذف','login'=>'دخول','logout'=>'خروج','export'=>'تصدير','view_sensitive'=>'بيانات حساسة'];
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                    <td class="px-4 py-3 font-medium text-gray-700">{{ $log->user?->name ?? 'النظام' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $actionColors[$log->action] ?? 'bg-gray-100 text-gray-700' }}" style="{{ $log->action === 'login' ? 'background:#e8f0f7;color:#0F4C75;' : '' }}">
                            {{ $actionLabels[$log->action] ?? $log->action }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">
                        @if($log->model_type)
                            {{ class_basename($log->model_type) }} #{{ $log->model_id }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-400 font-mono">{{ $log->ip_address }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">لا توجد سجلات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $logs->links() }}</div>
    @endif
</div>
</div>
@endsection
