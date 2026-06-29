@extends('layouts.app')
@section('title', 'صلاحيات ' . $user->name)
@section('page-title', 'إدارة الصلاحيات')
@section('back-url', route('users.index'))

@section('content')
@php
$pm = $permissionMap;

// Group permissions by their 'group' field
$groups = [];
foreach ($pm as $key => $p) {
    $groups[$p['group']][$key] = $p;
}

$grantedCount = collect($pm)->where('is_granted', true)->count();
$totalCount   = count($pm);
@endphp

<div x-data="{
    toast: { show: false, message: '', type: 'success' },
}" x-init="
    @if(session('success'))
    toast.message = '{{ addslashes(session('success')) }}';
    toast.type = 'success';
    toast.show = true;
    setTimeout(() => toast.show = false, 3500);
    @endif
    @if($errors->any())
    toast.message = '{{ addslashes($errors->first()) }}';
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
                <div class="text-center bg-slate-50 rounded-xl px-5 py-3 border border-slate-200">
                    <p class="text-2xl font-black text-slate-500">{{ $totalCount }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">إجمالي</p>
                </div>
                <div class="text-center bg-purple-50 rounded-xl px-5 py-3 border border-purple-100">
                    <p class="text-2xl font-black text-purple-700">{{ collect($pm)->where('is_custom', true)->count() }}</p>
                    <p class="text-xs text-purple-600 mt-0.5">مخصّصة</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Permission Groups ─── --}}
    @foreach($groups as $groupName => $permissions)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

        {{-- Group Header --}}
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between"
             style="background:linear-gradient(135deg,#f8fafc,#f1f5f9);">
            <h3 class="font-bold text-gray-700 text-sm">{{ $groupName }}</h3>
            @php
                $groupGranted = collect($permissions)->where('is_granted', true)->count();
                $groupTotal   = count($permissions);
            @endphp
            <span class="text-xs px-2.5 py-1 rounded-full font-semibold
                {{ $groupGranted === $groupTotal ? 'bg-green-100 text-green-700' : ($groupGranted > 0 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500') }}">
                {{ $groupGranted }}/{{ $groupTotal }}
            </span>
        </div>

        {{-- Permission Toggles Grid --}}
        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-2">
            @foreach($permissions as $key => $p)
            <form method="POST" action="{{ route('users.togglePermission', $user) }}"
                  x-data="{ busy: false }" @submit="busy = true"
                  class="flex items-center justify-between gap-3 px-4 py-3 rounded-xl border transition-all
                         {{ $p['is_granted']
                             ? ($p['is_custom'] ? 'bg-purple-50 border-purple-200' : 'bg-blue-50 border-blue-200')
                             : 'bg-gray-50 border-gray-200 hover:border-gray-300 hover:bg-gray-100' }}">
                @csrf
                <input type="hidden" name="permission" value="{{ $key }}">
                <input type="hidden" name="grant" value="{{ $p['is_granted'] ? '0' : '1' }}">

                <div class="flex items-center gap-2 min-w-0">
                    @if($p['is_custom'])
                        <span class="w-2 h-2 rounded-full bg-purple-500 flex-shrink-0"></span>
                    @elseif($p['is_granted'])
                        <span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0"></span>
                    @else
                        <span class="w-2 h-2 rounded-full bg-gray-300 flex-shrink-0"></span>
                    @endif
                    <span class="text-sm font-medium truncate
                                 {{ $p['is_granted'] ? ($p['is_custom'] ? 'text-purple-800' : 'text-blue-800') : 'text-gray-600' }}">
                        {{ $p['label'] }}
                    </span>
                </div>

                {{-- Toggle Switch Button --}}
                <button type="submit" :disabled="busy"
                        title="{{ $p['is_granted'] ? 'اضغط لإيقاف هذه الصلاحية' : 'اضغط لتفعيل هذه الصلاحية' }}"
                        class="relative flex-shrink-0 w-11 h-6 rounded-full transition-all duration-200 disabled:opacity-40 focus:outline-none focus:ring-2 focus:ring-offset-1
                               {{ $p['is_granted']
                                   ? ($p['is_custom'] ? 'bg-purple-500 focus:ring-purple-400' : 'bg-blue-500 focus:ring-blue-400')
                                   : 'bg-gray-300 hover:bg-gray-400 focus:ring-gray-400' }}">
                    <span class="absolute top-0.5 transition-all duration-200 w-5 h-5 bg-white rounded-full shadow-sm
                                 {{ $p['is_granted'] ? 'right-0.5' : 'left-0.5' }}"></span>
                </button>
            </form>
            @endforeach
        </div>
    </div>
    @endforeach

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
