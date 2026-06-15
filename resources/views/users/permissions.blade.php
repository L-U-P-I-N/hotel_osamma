@extends('layouts.app')
@section('title', 'صلاحيات ' . $user->name)
@section('page-title', 'إدارة صلاحيات الموظف')

@section('content')
<div class="max-w-2xl mx-auto">
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
        <a href="{{ route('users.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold" style="background:#0F4C75;">
            {{ mb_substr($user->name, 0, 1) }}
        </div>
        <div>
            <h2 class="font-bold text-gray-800">{{ $user->name }}</h2>
            <p class="text-xs text-gray-400">{{ $user->roles->first()?->name ?? 'موظف' }} — {{ $user->employee_id }}</p>
        </div>
    </div>

    <p class="text-sm text-gray-500 mb-5">انقر على أي صلاحية لتفعيلها أو إيقافها. التغييرات تُطبَّق فوراً.</p>

    <div class="space-y-2">
        @foreach($permissionMap as $key => $perm)
        <form method="POST" action="{{ route('users.togglePermission', $user) }}">
            @csrf
            <input type="hidden" name="permission" value="{{ $key }}">
            <input type="hidden" name="grant" value="{{ $perm['is_granted'] ? '0' : '1' }}">

            <button type="submit"
                    class="w-full flex items-center justify-between px-4 py-3 rounded-xl border-2 transition-all text-right
                           {{ $perm['is_granted'] ? 'border-green-300 bg-green-50 hover:bg-green-100' : 'border-gray-200 bg-gray-50 hover:bg-gray-100' }}">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0
                                {{ $perm['is_granted'] ? 'bg-green-500' : 'bg-gray-300' }}">
                        @if($perm['is_granted'])
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        @endif
                    </div>
                    <div class="text-sm">
                        <p class="font-medium {{ $perm['is_granted'] ? 'text-green-800' : 'text-gray-600' }}">{{ $perm['label'] }}</p>
                        <p class="text-xs {{ $perm['is_granted'] ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $perm['is_granted'] ? 'مفعّلة' : 'موقوفة' }}
                            @if($perm['is_custom'])
                            <span class="mr-1 text-xs opacity-60">(معدّلة)</span>
                            @elseif($perm['default'])
                            <span class="mr-1 text-xs opacity-60">(افتراضي)</span>
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

    <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-100 text-xs text-blue-700">
        الصلاحيات التالية للمدير فقط ولا يمكن منحها: <strong>إدارة المستخدمين، إدارة الغرف، سجل المراجعة</strong>
    </div>
</div>
</div>
@endsection
