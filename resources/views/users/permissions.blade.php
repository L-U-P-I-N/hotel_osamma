@extends('layouts.app')
@section('title', 'صلاحيات ' . $user->name)
@section('page-title', 'إدارة صلاحيات الموظف')

@section('content')
<div class="max-w-2xl mx-auto" x-data="{ toast: { show: false, message: '', type: 'success' } }" x-init="
    @if(session('success'))
    toast.message = '{{ session('success') }}';
    toast.type = 'success';
    toast.show = true;
    setTimeout(() => toast.show = false, 3000);
    @endif
">
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
        <a href="{{ route('users.index') }}" class="inline-flex items-center justify-center w-8 h-8 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold" style="background:#0F4C75;">
            {{ mb_substr($user->name, 0, 1) }}
        </div>
        <div>
            <h2 class="font-bold text-gray-800">{{ $user->name }}</h2>
            <p class="text-xs text-gray-400">{{ $user->roles->first()?->name ?? 'موظف' }} — {{ $user->employee_id }}</p>
        </div>
    </div>

    <p class="text-sm text-gray-500 mb-6">انقر على أي صلاحية لتفعيلها أو إيقافها. التغييرات تُطبَّق فوراً.</p>

    @php
    $groupedPermissions = [];
    foreach($permissionMap as $key => $perm) {
        $group = $perm['group'] ?? 'عام';
        if (!isset($groupedPermissions[$group])) {
            $groupedPermissions[$group] = [];
        }
        $groupedPermissions[$group][$key] = $perm;
    }
    @endphp

    @foreach($groupedPermissions as $groupName => $permissions)
    <div class="mb-6">
        <h3 class="font-semibold text-gray-700 text-sm mb-3 flex items-center gap-2">
            <div class="w-1 h-4 rounded-full" style="background:#0F4C75;"></div>
            {{ $groupName }}
        </h3>
        <div class="space-y-2 pr-3">
            @foreach($permissions as $key => $perm)
            <form method="POST" action="{{ route('users.togglePermission', $user) }}" class="inline-block w-full">
                @csrf
                <input type="hidden" name="permission" value="{{ $key }}">
                <input type="hidden" name="grant" value="{{ $perm['is_granted'] ? '0' : '1' }}">

                <button type="submit"
                        class="w-full flex items-center justify-between px-4 py-3 rounded-xl border-2 transition-all text-right cursor-pointer
                               {{ $perm['is_granted'] ? 'border-green-300 bg-green-50 hover:bg-green-100' : 'border-gray-200 bg-gray-50 hover:bg-gray-100' }}">
                    <div class="flex items-center gap-3 flex-1">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0
                                    {{ $perm['is_granted'] ? 'bg-green-500' : 'bg-gray-300' }}">
                            @if($perm['is_granted'])
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            @else
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            @endif
                        </div>
                        <div class="text-sm">
                            <p class="font-medium {{ $perm['is_granted'] ? 'text-green-800' : 'text-gray-700' }}">{{ $perm['label'] }}</p>
                            <p class="text-xs {{ $perm['is_granted'] ? 'text-green-600' : 'text-gray-500' }}">
                                {{ $perm['is_granted'] ? '✓ مفعّلة' : '✗ موقوفة' }}
                                @if($perm['is_custom'])
                                <span class="mx-1">—</span>
                                <span class="text-gray-500">(معدّلة شخصياً)</span>
                                @elseif($perm['default'])
                                <span class="mx-1">—</span>
                                <span class="text-gray-500">(حسب الدور: {{ $user->roles->first()?->name ?? 'موظف' }})</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="w-12 h-6 rounded-full transition-colors {{ $perm['is_granted'] ? 'bg-green-500' : 'bg-gray-300' }} relative flex-shrink-0">
                        <div class="w-5 h-5 bg-white rounded-full shadow absolute top-0.5 transition-all {{ $perm['is_granted'] ? 'left-6' : 'left-0.5' }}"></div>
                    </div>
                </button>
            </form>
            @endforeach
        </div>
    </div>
    @endforeach

    <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-100 text-xs text-blue-700">
        <strong>ملاحظة:</strong> الصلاحيات التالية مقتصرة على المدير فقط: إدارة المستخدمين، إدارة الغرف، سجل المراجعة
    </div>
</div>

<!-- Toast Notification -->
<div x-show="toast.show" x-cloak x-transition class="fixed bottom-4 right-4 px-6 py-3 rounded-lg text-white shadow-lg"
     :class="toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'">
    <div class="flex items-center gap-2">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span x-text="toast.message"></span>
    </div>
</div>
</div>
@endsection
