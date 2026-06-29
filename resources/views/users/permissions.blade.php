@extends('layouts.app')
@section('title', 'صلاحيات ' . $user->name)
@section('page-title', 'إدارة الصلاحيات')
@section('back-url', route('users.index'))

@section('content')
@php
$pm = $permissionMap;

$groups = [];
foreach ($pm as $key => $p) {
    $groups[$p['group']][$key] = $p;
}

$grantedCount = collect($pm)->where('is_granted', true)->count();
$totalCount   = count($pm);
@endphp

@push('styles')
<style>
/* ── Toggle switch ─────────────────────────────────────────── */
.perm-toggle-on  { background:#2d6aab; }
.perm-toggle-off { background:#9ca3af; }
html.dark .perm-toggle-on  { background:#1e578f; }
html.dark .perm-toggle-off { background:#374151; }

.perm-toggle-on:hover  { background:#1e578f; }
.perm-toggle-off:hover { background:#6b7280; }
html.dark .perm-toggle-off:hover { background:#4b5563; }

/* ── Permission card ───────────────────────────────────────── */
.perm-card-on {
    background: rgba(30,87,143,0.08);
    border-color: rgba(30,87,143,0.25);
}
.perm-card-off {
    background: transparent;
    border-color: #e5e7eb;
}
.perm-card-on:hover  { background: rgba(30,87,143,0.13); }
.perm-card-off:hover { background: rgba(0,0,0,0.02); }

html.dark .perm-card-on {
    background: rgba(30,87,143,0.15);
    border-color: rgba(30,87,143,0.35);
}
html.dark .perm-card-off {
    background: rgba(255,255,255,0.02);
    border-color: #2f3e54;
}
html.dark .perm-card-on:hover  { background: rgba(30,87,143,0.22); }
html.dark .perm-card-off:hover { background: rgba(255,255,255,0.05); }

/* Custom (manually set) card */
.perm-card-custom {
    background: rgba(109,40,217,0.07);
    border-color: rgba(109,40,217,0.20);
}
html.dark .perm-card-custom {
    background: rgba(109,40,217,0.12);
    border-color: rgba(109,40,217,0.28);
}

/* ── Group header ──────────────────────────────────────────── */
.perm-group-header {
    background: #f8fafc;
    border-bottom: 1px solid #e9ecef;
}
html.dark .perm-group-header {
    background: #1a2740;
    border-bottom: 1px solid #2f3e54;
}

/* ── Group badge ───────────────────────────────────────────── */
.badge-full    { background:#dcfce7; color:#166534; }
.badge-partial { background:#fef9c3; color:#854d0e; }
.badge-empty   { background:#f1f5f9; color:#64748b; }
html.dark .badge-full    { background:rgba(34,197,94,.15);  color:#4ade80; }
html.dark .badge-partial { background:rgba(234,179,8,.15);  color:#fbbf24; }
html.dark .badge-empty   { background:rgba(148,163,184,.10);color:#94a3b8; }

/* ── Dot indicator ─────────────────────────────────────────── */
.dot-on     { background:#2d6aab; }
.dot-off    { background:#d1d5db; }
.dot-custom { background:#7c3aed; }
html.dark .dot-on  { background:#5b90c5; }
html.dark .dot-off { background:#4b5563; }

/* ── Text ──────────────────────────────────────────────────── */
.perm-label-on  { color:#1e3a5f; }
.perm-label-off { color:#6b7280; }
html.dark .perm-label-on  { color:#c5d8ea; }
html.dark .perm-label-off { color:#64748b; }

/* ── Top stats ─────────────────────────────────────────────── */
.stat-granted { background:#f0fdf4; border:1px solid #bbf7d0; }
.stat-total   { background:#f8fafc; border:1px solid #e2e8f0; }
.stat-custom  { background:#faf5ff; border:1px solid #e9d5ff; }
html.dark .stat-granted { background:rgba(34,197,94,.08);  border-color:rgba(34,197,94,.20); }
html.dark .stat-total   { background:rgba(148,163,184,.06);border-color:#2f3e54; }
html.dark .stat-custom  { background:rgba(109,40,217,.08); border-color:rgba(109,40,217,.20);}
</style>
@endpush

<div x-data="{ toast: { show: false, message: '', type: 'success' } }"
     x-init="
    @if(session('success'))
    toast.message = '{{ addslashes(session('success')) }}'; toast.type = 'success';
    toast.show = true; setTimeout(() => toast.show = false, 3500);
    @endif
    @if($errors->any())
    toast.message = '{{ addslashes($errors->first()) }}'; toast.type = 'error';
    toast.show = true; setTimeout(() => toast.show = false, 4000);
    @endif
">

<div class="max-w-5xl mx-auto space-y-5">

    {{-- ─── User Card ─── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                @php
                    $avatarColors = ['#0F4C75','#1a6fa8','#065f46','#92400e','#6d28d9','#be123c'];
                    $avatarColor  = $avatarColors[crc32($user->name) % count($avatarColors)];
                @endphp
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white font-bold text-xl shadow-sm flex-shrink-0"
                     style="background:{{ $avatarColor }};">
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

            <div class="flex items-center gap-3">
                <div class="stat-granted text-center rounded-xl px-5 py-3">
                    <p class="text-2xl font-black text-green-700">{{ $grantedCount }}</p>
                    <p class="text-xs text-green-600 mt-0.5">مفعّلة</p>
                </div>
                <div class="stat-total text-center rounded-xl px-5 py-3">
                    <p class="text-2xl font-black text-slate-500">{{ $totalCount }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">إجمالي</p>
                </div>
                <div class="stat-custom text-center rounded-xl px-5 py-3">
                    <p class="text-2xl font-black text-purple-700">{{ collect($pm)->where('is_custom', true)->count() }}</p>
                    <p class="text-xs text-purple-500 mt-0.5">مخصّصة</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Permission Groups ─── --}}
    @foreach($groups as $groupName => $permissions)
    @php
        $groupGranted = collect($permissions)->where('is_granted', true)->count();
        $groupTotal   = count($permissions);
        $badgeClass   = $groupGranted === $groupTotal ? 'badge-full' : ($groupGranted > 0 ? 'badge-partial' : 'badge-empty');
    @endphp
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

        <div class="perm-group-header px-5 py-3 flex items-center justify-between">
            <h3 class="font-semibold text-gray-700 text-sm">{{ $groupName }}</h3>
            <span class="{{ $badgeClass }} text-xs px-2.5 py-1 rounded-full font-semibold">
                {{ $groupGranted }}/{{ $groupTotal }}
            </span>
        </div>

        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-2">
            @foreach($permissions as $key => $p)
            @php
                $isOn     = $p['is_granted'];
                $isCustom = $p['is_custom'];
                $cardClass  = $isCustom ? 'perm-card-custom' : ($isOn ? 'perm-card-on' : 'perm-card-off');
                $dotClass   = $isCustom ? 'dot-custom' : ($isOn ? 'dot-on' : 'dot-off');
                $labelClass = $isOn ? 'perm-label-on' : 'perm-label-off';
            @endphp
            <form method="POST" action="{{ route('users.togglePermission', $user) }}"
                  x-data="{ busy: false }" @submit="busy = true"
                  class="flex items-center justify-between gap-3 px-4 py-3 rounded-xl border transition-all {{ $cardClass }}">
                @csrf
                <input type="hidden" name="permission" value="{{ $key }}">
                <input type="hidden" name="grant" value="{{ $isOn ? '0' : '1' }}">

                <div class="flex items-center gap-2 min-w-0">
                    <span class="{{ $dotClass }} w-2 h-2 rounded-full flex-shrink-0"></span>
                    <span class="text-sm font-medium truncate {{ $labelClass }}">{{ $p['label'] }}</span>
                </div>

                {{-- Toggle Switch --}}
                <button type="submit" :disabled="busy"
                        title="{{ $isOn ? 'إيقاف: ' : 'تفعيل: ' }}{{ $p['label'] }}"
                        class="relative flex-shrink-0 w-11 h-6 rounded-full transition-all duration-200
                               disabled:opacity-40 focus:outline-none
                               {{ $isOn ? 'perm-toggle-on' : 'perm-toggle-off' }}">
                    <span class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-all duration-200
                                 {{ $isOn ? 'right-0.5' : 'left-0.5' }}"></span>
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
     x-transition:leave-end="opacity-0 translate-y-2"
     class="fixed bottom-5 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 px-5 py-3 rounded-xl text-white text-sm font-medium shadow-xl"
     :class="toast.type === 'success' ? 'bg-green-700' : 'bg-red-700'">
    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
    </svg>
    <span x-text="toast.message"></span>
</div>

</div>
@endsection
