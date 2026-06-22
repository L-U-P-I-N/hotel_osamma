@extends('layouts.app')
@section('title', 'صلاحيات ' . $user->name)
@section('page-title', 'إدارة الصلاحيات')

@section('content')
<div x-data="{
    toast: { show: false, message: '', type: 'success' },
    loading: null
}" x-init="
    @if(session('success'))
    toast.message = '{{ addslashes(session('success')) }}';
    toast.type = 'success';
    toast.show = true;
    setTimeout(() => toast.show = false, 3000);
    @endif
">

<div class="max-w-3xl mx-auto">

    {{-- ─── User Card ─────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5 flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('users.index') }}"
               class="w-9 h-9 flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            @php
                $colors = ['#0F4C75','#1a6fa8','#065f46','#92400e','#6d28d9','#be123c'];
                $color  = $colors[crc32($user->name) % count($colors)];
            @endphp
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white font-bold text-lg shadow-sm flex-shrink-0"
                 style="background:{{ $color }};">
                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
            </div>
            <div>
                <h2 class="font-bold text-gray-900 text-base">{{ $user->name }}</h2>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-xs px-2 py-0.5 rounded-lg font-medium" style="background:#e8f0f7; color:#0F4C75;">
                        {{ $user->roles->first()?->name ?? 'موظف' }}
                    </span>
                    <span class="text-xs text-gray-400">{{ $user->employee_id }}</span>
                    @if($user->is_active)
                    <span class="inline-flex items-center gap-1 text-xs text-green-700">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>نشط
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 text-xs text-red-600">
                        <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>معطّل
                    </span>
                    @endif
                </div>
            </div>
        </div>

        @php
            $grantedCount = collect($permissionMap)->where('is_granted', true)->count();
            $totalCount   = count($permissionMap);
        @endphp
        <div class="flex items-center gap-3">
            <div class="text-center bg-gray-50 rounded-xl px-4 py-2 border border-gray-100">
                <p class="text-xl font-bold" style="color:#0F4C75;">{{ $grantedCount }}</p>
                <p class="text-xs text-gray-400">مفعّلة</p>
            </div>
            <div class="text-center bg-gray-50 rounded-xl px-4 py-2 border border-gray-100">
                <p class="text-xl font-bold text-gray-400">{{ $totalCount - $grantedCount }}</p>
                <p class="text-xs text-gray-400">موقوفة</p>
            </div>
        </div>
    </div>

    {{-- ─── Info Note ──────────────────────────────────────────────────────── --}}
    <div class="flex items-start gap-2.5 bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 mb-5 text-sm text-blue-700">
        <svg class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
        <span>انقر على أي صلاحية لتفعيلها أو إيقافها فوراً. الصلاحيات المميّزة بـ <strong>معدّلة شخصياً</strong> تتجاوز الإعدادات الافتراضية للدور.</span>
    </div>

    {{-- ─── Permission Groups ──────────────────────────────────────────────── --}}
    @php
    $groupedPermissions = [];
    foreach($permissionMap as $key => $perm) {
        $group = $perm['group'] ?? 'عام';
        $groupedPermissions[$group][$key] = $perm;
    }
    @endphp

    <div class="space-y-4">
    @foreach($groupedPermissions as $groupName => $permissions)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {{-- Group Header --}}
        <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800 text-sm flex items-center gap-2">
                {{ $groupName }}
            </h3>
            @php
                $groupGranted = collect($permissions)->where('is_granted', true)->count();
                $groupTotal   = count($permissions);
            @endphp
            <span class="text-xs px-2 py-0.5 rounded-full font-medium
                {{ $groupGranted === $groupTotal ? 'bg-green-100 text-green-700' : ($groupGranted > 0 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500') }}">
                {{ $groupGranted }}/{{ $groupTotal }}
            </span>
        </div>

        {{-- Permissions List --}}
        <div class="divide-y divide-gray-50">
            @foreach($permissions as $key => $perm)
            <form method="POST" action="{{ route('users.togglePermission', $user) }}"
                  x-data="{ busy: false }"
                  @submit="busy = true">
                @csrf
                <input type="hidden" name="permission" value="{{ $key }}">
                <input type="hidden" name="grant" value="{{ $perm['is_granted'] ? '0' : '1' }}">

                <button type="submit"
                        :disabled="busy"
                        class="w-full flex items-center justify-between px-5 py-3.5 transition-colors text-right group
                               hover:{{ $perm['is_granted'] ? 'bg-green-50' : 'bg-gray-50' }}
                               {{ $perm['is_granted'] ? 'bg-green-50/30' : '' }}
                               disabled:opacity-60 disabled:cursor-wait">

                    {{-- Left: indicator + text --}}
                    <div class="flex items-center gap-3.5 flex-1 min-w-0">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors
                                    {{ $perm['is_granted'] ? 'bg-green-100' : 'bg-gray-100' }}">
                            @if($perm['is_granted'])
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            @else
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm font-medium {{ $perm['is_granted'] ? 'text-gray-900' : 'text-gray-600' }}">
                                {{ $perm['label'] }}
                            </p>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                @if($perm['is_custom'])
                                <span class="text-xs px-1.5 py-0.5 bg-purple-100 text-purple-700 rounded font-medium">معدّلة شخصياً</span>
                                @elseif($perm['default'])
                                <span class="text-xs text-gray-400">افتراضي للدور</span>
                                @else
                                <span class="text-xs text-gray-400">غير ممنوحة للدور</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Right: toggle switch --}}
                    <div class="mr-4 flex-shrink-0">
                        <div class="w-11 h-6 rounded-full transition-colors relative
                                    {{ $perm['is_granted'] ? 'bg-green-500' : 'bg-gray-200' }}">
                            <div class="w-4.5 h-4.5 w-[18px] h-[18px] bg-white rounded-full shadow-sm absolute top-[3px] transition-all
                                        {{ $perm['is_granted'] ? 'left-[23px]' : 'left-[3px]' }}"></div>
                        </div>
                    </div>
                </button>
            </form>
            @endforeach
        </div>
    </div>
    @endforeach
    </div>

    {{-- Admin-only note --}}
    <div class="mt-4 flex items-start gap-2 bg-amber-50 border border-amber-100 rounded-xl px-4 py-3 text-xs text-amber-700">
        <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span>الصلاحيات المقتصرة على المدير (إدارة المستخدمين، سجل المراجعة) لا تظهر هنا ولا يمكن منحها للموظفين.</span>
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
