@extends('layouts.app')
@section('title', 'صلاحيات ' . $user->name)
@section('page-title', 'إدارة الصلاحيات')
@section('back-url', route('users.index'))

@section('content')
@php
$pm = $permissionMap; // shorthand

// Helper: get permission state for a key
$perm = fn(string $key) => $pm[$key] ?? null;
$granted = fn(?array $p) => $p && $p['is_granted'];
$custom  = fn(?array $p) => $p && $p['is_custom'];

// CRUD Groups definition
$crudGroups = [
    [
        'icon'   => '📋',
        'name'   => 'الحجوزات والإقامة',
        'view'   => 'checkin.view',
        'create' => 'checkin.create',
        'edit'   => null,
        'delete' => null,
        'extra'  => ['checkout.process' => 'تسجيل الخروج'],
    ],
    [
        'icon'   => '👤',
        'name'   => 'النزلاء',
        'view'   => 'guests.sensitive',
        'create' => null,
        'edit'   => 'guest.edit',
        'delete' => null,
        'extra'  => [],
    ],
    [
        'icon'   => '🛏️',
        'name'   => 'إدارة الغرف',
        'view'   => 'rooms.view',
        'create' => 'rooms.create',
        'edit'   => 'rooms.edit',
        'delete' => 'rooms.delete',
        'extra'  => [
            'rooms.maintenance' => 'تغيير الحالة',
            'room.price.edit'   => 'تعديل السعر',
        ],
    ],
    [
        'icon'   => '💰',
        'name'   => 'المالية',
        'view'   => 'payments.bank_receipt',
        'create' => 'payments.create',
        'edit'   => null,
        'delete' => null,
        'extra'  => ['withdrawal.create' => 'تسجيل سحب'],
    ],
    [
        'icon'   => '⏰',
        'name'   => 'الورديات',
        'view'   => 'shifts.view',
        'create' => null,
        'edit'   => null,
        'delete' => null,
        'extra'  => ['shifts.reopen' => 'إعادة فتح إقفال'],
    ],
    [
        'icon'   => '📊',
        'name'   => 'التقارير',
        'view'   => 'reports.view',
        'create' => null,
        'edit'   => null,
        'delete' => null,
        'extra'  => [
            'report.monthly'    => 'تقرير شهري',
            'government.export' => 'تصدير حكومي',
        ],
    ],
    [
        'icon'   => '👥',
        'name'   => 'الموارد البشرية',
        'view'   => 'hr.view',
        'create' => 'hr.create',
        'edit'   => 'hr.edit',
        'delete' => 'hr.delete',
        'extra'  => [],
    ],
    [
        'icon'   => '💸',
        'name'   => 'المصروفات',
        'view'   => 'expenses.view',
        'create' => 'expenses.create',
        'edit'   => 'expenses.edit',
        'delete' => 'expenses.delete',
        'extra'  => [],
    ],
    [
        'icon'   => '✅',
        'name'   => 'الحضور والغياب',
        'view'   => 'attendance.view',
        'create' => 'attendance.create',
        'edit'   => null,
        'delete' => null,
        'extra'  => [],
    ],
];

$grantedCount = collect($pm)->where('is_granted', true)->count();
$totalCount   = count($pm);
@endphp

<div x-data="{
    toast: { show: false, message: '', type: 'success' },
    loading: null
}" x-init="
    @if(session('success'))
    toast.message = '{{ addslashes(session('success')) }}';
    toast.type = 'success';
    toast.show = true;
    setTimeout(() => toast.show = false, 3500);
    @endif
    @if(session('error'))
    toast.message = '{{ addslashes(session('error')) }}';
    toast.type = 'error';
    toast.show = true;
    setTimeout(() => toast.show = false, 4000);
    @endif
">

<div class="max-w-5xl mx-auto space-y-5">

    {{-- ─── User Card ─── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                @php
                    $colors = ['#0F4C75','#1a6fa8','#065f46','#92400e','#6d28d9','#be123c'];
                    $color  = $colors[crc32($user->name) % count($colors)];
                @endphp
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white font-bold text-xl shadow-sm flex-shrink-0"
                     style="background:{{ $color }};">
                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h2 class="font-bold text-gray-900 text-lg">{{ $user->name }}</h2>
                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                        <span class="text-xs px-2.5 py-1 rounded-lg font-semibold" style="background:#e8f0f7; color:#0F4C75;">
                            {{ $user->roles->first()?->name ?? 'موظف' }}
                        </span>
                        <span class="text-xs text-gray-400">{{ $user->employee_id }}</span>
                        @if($user->is_active)
                        <span class="inline-flex items-center gap-1 text-xs text-green-700 bg-green-50 px-2 py-0.5 rounded-full">
                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>نشط
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 text-xs text-red-600 bg-red-50 px-2 py-0.5 rounded-full">
                            <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>معطّل
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="flex items-center gap-3">
                <div class="text-center bg-green-50 rounded-xl px-5 py-3 border border-green-100">
                    <p class="text-2xl font-black text-green-700">{{ $grantedCount }}</p>
                    <p class="text-xs text-green-600 mt-0.5">مفعّلة</p>
                </div>
                <div class="text-center bg-gray-50 rounded-xl px-5 py-3 border border-gray-100">
                    <p class="text-2xl font-black text-gray-400">{{ $totalCount - $grantedCount }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">موقوفة</p>
                </div>
                <div class="text-center bg-purple-50 rounded-xl px-5 py-3 border border-purple-100">
                    <p class="text-2xl font-black text-purple-700">{{ collect($pm)->where('is_custom', true)->count() }}</p>
                    <p class="text-xs text-purple-600 mt-0.5">مخصّصة</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Legend ─── --}}
    <div class="flex flex-wrap gap-3 text-xs">
        <div class="flex items-center gap-1.5 bg-white border border-gray-100 rounded-lg px-3 py-2 shadow-sm">
            <div class="w-2.5 h-2.5 rounded-full bg-green-500"></div>
            <span class="text-gray-600">مفعّلة</span>
        </div>
        <div class="flex items-center gap-1.5 bg-white border border-gray-100 rounded-lg px-3 py-2 shadow-sm">
            <div class="w-2.5 h-2.5 rounded-full bg-gray-300"></div>
            <span class="text-gray-600">موقوفة</span>
        </div>
        <div class="flex items-center gap-1.5 bg-white border border-gray-100 rounded-lg px-3 py-2 shadow-sm">
            <div class="w-2.5 h-2.5 rounded-full bg-purple-500"></div>
            <span class="text-gray-600">مخصّصة يدوياً</span>
        </div>
        <div class="flex items-center gap-1.5 bg-white border border-gray-100 rounded-lg px-3 py-2 shadow-sm">
            <div class="w-2.5 h-2.5 rounded-full bg-gray-200 border border-dashed border-gray-400"></div>
            <span class="text-gray-400">غير متاحة لهذه المجموعة</span>
        </div>
    </div>

    {{-- ─── Permissions Table ─── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

        {{-- Table Header --}}
        <div class="grid border-b border-gray-100" style="grid-template-columns: 1fr 80px 80px 80px 80px 1fr;">
            <div class="px-5 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">المجموعة</div>
            <div class="px-2 py-3 text-center text-xs font-bold text-blue-600">
                <div class="flex flex-col items-center gap-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    قراءة
                </div>
            </div>
            <div class="px-2 py-3 text-center text-xs font-bold text-green-600">
                <div class="flex flex-col items-center gap-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    إضافة
                </div>
            </div>
            <div class="px-2 py-3 text-center text-xs font-bold text-amber-600">
                <div class="flex flex-col items-center gap-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    تعديل
                </div>
            </div>
            <div class="px-2 py-3 text-center text-xs font-bold text-red-600">
                <div class="flex flex-col items-center gap-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    حذف
                </div>
            </div>
            <div class="px-5 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">إجراءات خاصة</div>
        </div>

        {{-- Rows --}}
        <div class="divide-y divide-gray-50">
        @foreach($crudGroups as $group)
        @php
            $vPerm = $group['view']   ? $perm($group['view'])   : null;
            $cPerm = $group['create'] ? $perm($group['create']) : null;
            $ePerm = $group['edit']   ? $perm($group['edit'])   : null;
            $dPerm = $group['delete'] ? $perm($group['delete']) : null;

            // Count active in this group
            $groupKeys = array_filter([$group['view'], $group['create'], $group['edit'], $group['delete']]);
            foreach ($group['extra'] as $k => $l) { $groupKeys[] = $k; }
            $groupActive = collect($groupKeys)->filter(fn($k) => $pm[$k]['is_granted'] ?? false)->count();
            $groupTotal  = count($groupKeys);
        @endphp
        <div class="grid hover:bg-gray-50/50 transition-colors group" style="grid-template-columns: 1fr 80px 80px 80px 80px 1fr;">

            {{-- Group Name --}}
            <div class="px-5 py-4 flex items-center gap-3">
                <span class="text-xl leading-none">{{ $group['icon'] }}</span>
                <div>
                    <p class="text-sm font-semibold text-gray-800">{{ $group['name'] }}</p>
                    <p class="text-xs mt-0.5 {{ $groupActive === $groupTotal ? 'text-green-600' : ($groupActive > 0 ? 'text-amber-600' : 'text-gray-400') }}">
                        {{ $groupActive }}/{{ $groupTotal }} صلاحية مفعّلة
                    </p>
                </div>
            </div>

            {{-- View --}}
            <div class="flex items-center justify-center py-4">
                @if($group['view'] && $vPerm)
                    @include('users._perm_toggle', ['key' => $group['view'], 'p' => $vPerm, 'user' => $user, 'color' => 'blue'])
                @else
                    <div class="w-9 h-9 rounded-xl bg-gray-100/50 flex items-center justify-center border-2 border-dashed border-gray-200">
                        <span class="text-gray-300 text-xs">—</span>
                    </div>
                @endif
            </div>

            {{-- Create --}}
            <div class="flex items-center justify-center py-4">
                @if($group['create'] && $cPerm)
                    @include('users._perm_toggle', ['key' => $group['create'], 'p' => $cPerm, 'user' => $user, 'color' => 'green'])
                @else
                    <div class="w-9 h-9 rounded-xl bg-gray-100/50 flex items-center justify-center border-2 border-dashed border-gray-200">
                        <span class="text-gray-300 text-xs">—</span>
                    </div>
                @endif
            </div>

            {{-- Edit --}}
            <div class="flex items-center justify-center py-4">
                @if($group['edit'] && $ePerm)
                    @include('users._perm_toggle', ['key' => $group['edit'], 'p' => $ePerm, 'user' => $user, 'color' => 'amber'])
                @else
                    <div class="w-9 h-9 rounded-xl bg-gray-100/50 flex items-center justify-center border-2 border-dashed border-gray-200">
                        <span class="text-gray-300 text-xs">—</span>
                    </div>
                @endif
            </div>

            {{-- Delete --}}
            <div class="flex items-center justify-center py-4">
                @if($group['delete'] && $dPerm)
                    @include('users._perm_toggle', ['key' => $group['delete'], 'p' => $dPerm, 'user' => $user, 'color' => 'red'])
                @else
                    <div class="w-9 h-9 rounded-xl bg-gray-100/50 flex items-center justify-center border-2 border-dashed border-gray-200">
                        <span class="text-gray-300 text-xs">—</span>
                    </div>
                @endif
            </div>

            {{-- Extra Permissions --}}
            <div class="px-5 py-4 flex items-center gap-2 flex-wrap">
                @forelse($group['extra'] as $extraKey => $extraLabel)
                @php $ep = $perm($extraKey); @endphp
                @if($ep)
                <form method="POST" action="{{ route('users.togglePermission', $user) }}"
                      x-data="{ busy: false }" @submit="busy = true"
                      class="inline-flex">
                    @csrf
                    <input type="hidden" name="permission" value="{{ $extraKey }}">
                    <input type="hidden" name="grant" value="{{ $ep['is_granted'] ? '0' : '1' }}">
                    <button type="submit" :disabled="busy"
                            title="{{ $ep['label'] }}"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-all border disabled:opacity-50
                                   {{ $ep['is_granted']
                                        ? ($ep['is_custom'] ? 'bg-purple-100 text-purple-800 border-purple-200 hover:bg-purple-200' : 'bg-indigo-50 text-indigo-700 border-indigo-200 hover:bg-indigo-100')
                                        : 'bg-gray-50 text-gray-500 border-gray-200 hover:bg-gray-100' }}">
                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $ep['is_granted'] ? ($ep['is_custom'] ? 'bg-purple-500' : 'bg-indigo-500') : 'bg-gray-300' }}"></span>
                        {{ $extraLabel }}
                    </button>
                </form>
                @endif
                @empty
                <span class="text-xs text-gray-300">—</span>
                @endforelse
            </div>

        </div>
        @endforeach
        </div>
    </div>

    {{-- ─── Admin-only note ─── --}}
    <div class="flex items-start gap-2.5 bg-amber-50 border border-amber-100 rounded-xl px-4 py-3 text-xs text-amber-700">
        <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span>صلاحيات المدير (إدارة المستخدمين، سجل المراجعة) محجوزة للمدير فقط ولا تظهر هنا.</span>
    </div>

</div>

{{-- Toast --}}
<div x-show="toast.show" x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-2"
     class="fixed bottom-5 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 px-5 py-3 rounded-xl text-white text-sm font-medium shadow-xl"
     :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'">
    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
    </svg>
    <span x-text="toast.message"></span>
</div>

</div>
@endsection
