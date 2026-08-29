@extends('layouts.app')
@section('title', 'سجل المراجعة')
@section('page-title', 'سجل المراجعة والتغييرات المالية')

@section('content')
<div class="space-y-5" dir="rtl">

{{-- Summary Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">إجمالي الأحداث</p>
                <p class="text-2xl font-bold text-blue-600">{{ $logs->total() }}</p>
            </div>
            <div class="text-3xl">📊</div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-green-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">آخر نشاط</p>
                <p class="text-sm font-bold text-green-600">{{ $logs->first()?->created_at->diffForHumans() ?? 'لا توجد أحداث' }}</p>
            </div>
            <div class="text-3xl">⏰</div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-purple-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 mb-1">عدد الموظفين النشطين</p>
                <p class="text-2xl font-bold text-purple-600">{{ $users->count() }}</p>
            </div>
            <div class="text-3xl">👥</div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <form method="GET" class="flex flex-wrap gap-3 items-end mb-4">
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
                <option value="create" {{ request('action')=='create'?'selected':'' }}>✨ إنشاء</option>
                <option value="update" {{ request('action')=='update'?'selected':'' }}>✏️ تحديث</option>
                <option value="delete" {{ request('action')=='delete'?'selected':'' }}>🗑️ حذف</option>
                <option value="login" {{ request('action')=='login'?'selected':'' }}>🔓 دخول</option>
                <option value="logout" {{ request('action')=='logout'?'selected':'' }}>🔒 خروج</option>
                <option value="export" {{ request('action')=='export'?'selected':'' }}>📤 تصدير</option>
                <option value="view_sensitive" {{ request('action')=='view_sensitive'?'selected':'' }}>👁️ بيانات حساسة</option>
            </select>
        </div>
        <button type="submit" class="sr-only">فلترة</button>
        @if(request()->hasAny(['user_id','action']))
        <a href="{{ route('audit.log') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-xs text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition self-end">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            مسح الفلترة
        </a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-700">سجل الأحداث والتغييرات</h3>
        <button onclick="window.print()" class="text-xs px-3 py-1.5 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition">
            🖨️ طباعة
        </button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ والوقت</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الموظف</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحدث</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النموذج / التفاصيل</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">عنوان IP</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @php
                    $actionEmojis = [
                        'create'=>'✨', 'update'=>'✏️', 'delete'=>'🗑️', 'login'=>'🔓',
                        'logout'=>'🔒', 'export'=>'📤', 'view_sensitive'=>'👁️',
                    ];
                    $actionColors = [
                        'create'=>'bg-green-100 text-green-800',
                        'update'=>'bg-blue-100 text-blue-800',
                        'delete'=>'bg-red-100 text-red-800',
                        'login'=>'bg-primary-100 text-primary-800',
                        'logout'=>'bg-gray-100 text-gray-800',
                        'export'=>'bg-yellow-100 text-yellow-800',
                        'view_sensitive'=>'bg-orange-100 text-orange-800',
                    ];
                    $actionLabels = [
                        'create'=>'إنشاء', 'update'=>'تحديث', 'delete'=>'حذف',
                        'login'=>'دخول', 'logout'=>'خروج', 'export'=>'تصدير',
                        'view_sensitive'=>'بيانات حساسة',
                    ];
                    $modelLabels = [
                        'Reservation' => 'حجز', 'Guest' => 'نزيل', 'Companion' => 'مرافق',
                        'Payment' => 'دفعة', 'Refund' => 'استرجاع', 'Expense' => 'مصروف',
                        'Room' => 'غرفة', 'Shift' => 'وردية', 'CashSettlement' => 'تسوية صندوق',
                        'CashWithdrawal' => 'سحب', 'Salary' => 'راتب', 'Employee' => 'موظف',
                        'User' => 'مستخدم', 'Leave' => 'إجازة', 'Attendance' => 'حضور',
                    ];
                    // تنسيق القيم (أسماء بدل المعرّفات، تسميات عربية بدل enum،
                    // ومبالغ مُنسَّقة) موحّد في AuditFormatter ليُقرأه المدير بسهولة.
                    $fmt = \App\Support\AuditFormatter::class;
                    $noiseFields = $fmt::NOISE_FIELDS;
                @endphp
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap font-medium">
                        {{ $log->created_at->format('d/m/Y H:i:s') }}
                        <br><span class="text-gray-400 text-xs">منذ {{ $log->created_at->diffForHumans() }}</span>
                    </td>
                    <td class="px-4 py-3 font-semibold text-gray-700">{{ $log->user?->name ?? 'النظام 🔧' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $actionColors[$log->action] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $actionEmojis[$log->action] ?? '📝' }} {{ $actionLabels[$log->action] ?? $log->action }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs">
                        <div class="mb-2">
                            @if($log->model_type)
                            @php $base = class_basename($log->model_type); @endphp
                            <span class="text-gray-700 font-semibold">{{ $modelLabels[$base] ?? $base }} #{{ $log->model_id }}</span>
                            @else
                            <span class="text-gray-500">-</span>
                            @endif
                        </div>

                        @php
                            $old = is_array($log->old_values) ? $log->old_values : null;
                            $new = is_array($log->new_values) ? $log->new_values : null;
                        @endphp

                        @if($old && $new)
                        {{-- تحديث: نعرض الحقول التي تغيّرت فعلياً فقط --}}
                        @php
                            $changed = collect($new)->reject(fn($v, $k) => in_array($k, $noiseFields, true))
                                ->filter(fn($v, $k) => ($old[$k] ?? null) != $v);
                        @endphp
                        @if($changed->isNotEmpty())
                        {{-- سطر مختصر يوضّح ما تغيّر دون الحاجة لفتح التفاصيل --}}
                        @php
                            $changedLabels = $changed->keys()->map(fn($k) => $fmt::fieldLabel($k));
                            $changedBrief  = $changedLabels->take(3)->implode('، ')
                                           . ($changedLabels->count() > 3 ? ' وغيرها' : '');
                        @endphp
                        <div class="text-xs text-gray-500 mb-1">تغيّر: <span class="text-gray-700 font-semibold">{{ $changedBrief }}</span></div>
                        <details class="cursor-pointer">
                            <summary class="text-xs text-blue-600 hover:text-blue-800 font-medium">عرض التغييرات بالتفصيل ({{ $changed->count() }})</summary>
                            <div class="mt-2 text-xs space-y-1 bg-gray-50 p-2 rounded">
                                @foreach($changed as $key => $newVal)
                                <div class="text-gray-600">
                                    <strong class="text-gray-700">{{ $fmt::fieldLabel($key) }}:</strong>
                                    <span class="line-through text-red-600">{{ $fmt::formatValue($key, $old[$key] ?? null) }}</span>
                                    <span class="text-green-600">→ {{ $fmt::formatValue($key, $newVal) }}</span>
                                </div>
                                @endforeach
                            </div>
                        </details>
                        @endif
                        @elseif($new)
                        {{-- إنشاء أو إجراء يحمل قيماً جديدة فقط: نعرض كل الحقول ذات القيمة --}}
                        @php
                            $shown = collect($new)->reject(fn($v, $k) => in_array($k, $noiseFields, true))->filter(fn($v) => $v !== null && $v !== '');
                        @endphp
                        @if($shown->isNotEmpty())
                        <details class="cursor-pointer">
                            <summary class="text-xs text-green-600 hover:text-green-800 font-medium">عرض التفاصيل ({{ $shown->count() }})</summary>
                            <div class="mt-2 text-xs space-y-1 bg-gray-50 p-2 rounded">
                                @foreach($shown as $key => $val)
                                <div class="text-gray-600"><strong class="text-gray-700">{{ $fmt::fieldLabel($key) }}:</strong> {{ $fmt::formatValue($key, $val) }}</div>
                                @endforeach
                            </div>
                        </details>
                        @endif
                        @elseif($old)
                        {{-- حذف: نعرض آخر حالة معروفة للسجل قبل حذفه --}}
                        @php
                            $shown = collect($old)->reject(fn($v, $k) => in_array($k, $noiseFields, true))->filter(fn($v) => $v !== null && $v !== '');
                        @endphp
                        @if($shown->isNotEmpty())
                        <details class="cursor-pointer">
                            <summary class="text-xs text-red-600 hover:text-red-800 font-medium">عرض بيانات ما قبل الحذف ({{ $shown->count() }})</summary>
                            <div class="mt-2 text-xs space-y-1 bg-gray-50 p-2 rounded">
                                @foreach($shown as $key => $val)
                                <div class="text-gray-600"><strong class="text-gray-700">{{ $fmt::fieldLabel($key) }}:</strong> {{ $fmt::formatValue($key, $val) }}</div>
                                @endforeach
                            </div>
                        </details>
                        @endif
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-400 font-mono">{{ $log->ip_address ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">📭 لا توجد سجلات</td></tr>
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
