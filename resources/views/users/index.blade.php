@extends('layouts.app')
@section('title', 'المستخدمون')
@section('page-title', 'إدارة المستخدمين')

@section('content')
<div x-data="{
    addModal: false,
    editModal: false,
    editUser: {},
    backupCodeModal: false,
    backupCode: '',
    backupCodeUser: '',
    toast: { show: false, message: '', type: 'success' },
    showToast(msg, type = 'success') {
        this.toast.message = msg;
        this.toast.type = type;
        this.toast.show = true;
        setTimeout(() => this.toast.show = false, 3500);
    }
}" x-init="
    @if(session('new_backup_code'))
    backupCode = '{{ session('new_backup_code') }}';
    backupCodeUser = '{{ session('new_backup_code_user') }}';
    backupCodeModal = true;
    @endif
    @if(session('success'))
    showToast('{{ addslashes(session('success')) }}');
    @endif
    @if($errors->any())
    showToast('{{ addslashes($errors->first()) }}', 'error');
    @endif
">

{{-- ─── Header & Stats ─────────────────────────────────────────────────── --}}
<div class="mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
        <div class="flex items-center gap-4">
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-4 py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:#e8f0f7;">
                        <svg class="w-5 h-5" style="color:#0F4C75;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 leading-none mb-0.5">الإجمالي</p>
                        <p class="text-lg font-bold text-gray-800 leading-none">{{ $totalCount }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-4 py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-green-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 leading-none mb-0.5">نشط</p>
                        <p class="text-lg font-bold text-green-700 leading-none">{{ $activeCount }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-4 py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 leading-none mb-0.5">معطّل</p>
                        <p class="text-lg font-bold text-red-600 leading-none">{{ $totalCount - $activeCount }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if(auth()->user()->isAdmin())
            <a href="{{ route('system.backup.download') }}"
               onclick="return confirm('سيُنزَّل ملف مضغوط يحتوي على قاعدة البيانات كاملة وصور هويات النزلاء (بيانات حساسة). تأكد أنك على جهاز موثوق قبل المتابعة. قد يستغرق التجهيز بضع دقائق حسب حجم البيانات — هل تريد المتابعة؟')"
               class="flex items-center gap-2 px-4 py-2.5 border border-amber-200 bg-amber-50 text-amber-800 rounded-xl text-sm hover:bg-amber-100 transition font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                </svg>
                تنزيل نسخة كاملة من النظام
            </a>
            @endif
            <a href="{{ route('audit.log') }}"
               class="flex items-center gap-2 px-4 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm hover:bg-gray-50 transition font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                سجل المراجعة
            </a>
            <button @click="addModal=true"
                    class="flex items-center gap-2 px-4 py-2.5 text-white rounded-xl text-sm font-medium transition shadow-sm hover:shadow-md"
                    style="background:#0F4C75;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                مستخدم جديد
            </button>
        </div>
    </div>

    {{-- Search & Filter --}}
    <form method="GET" class="bg-white rounded-xl border border-gray-100 shadow-sm p-3 flex flex-wrap gap-3 items-center">
        <div class="flex-1 min-w-52 relative">
            <svg class="w-4 h-4 text-gray-400 absolute top-1/2 -translate-y-1/2 right-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="ابحث بالاسم أو اسم المستخدم أو رقم الموظف..."
                   class="w-full border border-gray-200 rounded-lg pr-9 pl-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition">
        </div>
        <select name="role" onchange="this.form.submit()"
                class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white min-w-32">
            <option value="">جميع الأدوار</option>
            @foreach($roles as $role)
            <option value="{{ $role->name }}" {{ request('role') === $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
            @endforeach
        </select>
        <select name="status" onchange="this.form.submit()"
                class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white min-w-32">
            <option value="">جميع الحالات</option>
            <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>نشط</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>معطّل</option>
        </select>
        <button type="submit" class="px-4 py-2 text-sm font-medium text-white rounded-lg transition" style="background:#0F4C75;">بحث</button>
        @if(request()->hasAny(['search','role','status']))
        <a href="{{ route('users.index') }}"
           class="flex items-center gap-1 px-3 py-2 text-xs text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            مسح
        </a>
        @endif
    </form>
</div>

{{-- ─── Users Table ─────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between">
        <span class="text-sm font-semibold text-gray-700">
            قائمة المستخدمين
            <span class="mr-1.5 text-xs font-normal text-gray-400">({{ $users->total() }} مستخدم)</span>
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">الموظف</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">اسم الدخول</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">الجوال</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">الدور</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">الحالة</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($users as $user)
                @php
                    $colors = ['#0F4C75','#1a6fa8','#065f46','#92400e','#6d28d9','#be123c','#0369a1','#15803d'];
                    $color  = $colors[crc32($user->name) % count($colors)];
                    $isMe   = $user->id === auth()->id();
                    $isAdm  = $user->isAdmin();
                @endphp
                <tr class="hover:bg-blue-50/40 transition-colors group">
                    {{-- Name & ID --}}
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-sm"
                                 style="background:{{ $color }};">
                                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="font-semibold text-gray-800 flex items-center gap-1.5">
                                    {{ $user->name }}
                                    @if($isMe)
                                    <span class="text-[10px] px-1.5 py-0.5 rounded-md font-medium" style="background:#e8f0f7; color:#0F4C75;">أنت</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ $user->employee_id }}</div>
                            </div>
                        </div>
                    </td>

                    {{-- Username --}}
                    <td class="px-4 py-3.5">
                        <span class="font-mono text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded-lg">{{ $user->username }}</span>
                    </td>

                    {{-- Phone --}}
                    <td class="px-4 py-3.5 text-gray-600 text-sm">
                        {{ $user->phone ?: '—' }}
                    </td>

                    {{-- Role --}}
                    <td class="px-4 py-3.5">
                        @php $roleName = $user->roles->first()?->name ?? '—'; @endphp
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold"
                              style="background:#e8f0f7; color:#0F4C75;">
                            @if($isAdm)
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            @endif
                            {{ $roleName }}
                        </span>
                    </td>

                    {{-- Status --}}
                    <td class="px-4 py-3.5 text-center">
                        @if($user->is_active)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">
                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                            نشط
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">
                            <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                            معطّل
                        </span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="px-4 py-3.5">
                        <div class="flex items-center justify-center gap-1.5">

                            {{-- تعديل --}}
                            <button @click="editUser={{ json_encode(['id'=>$user->id,'name'=>$user->name,'phone'=>$user->phone,'role'=>$user->roles->first()?->name]) }}; editModal=true"
                                    title="تعديل البيانات"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition border border-gray-200 text-gray-600 hover:bg-gray-100 hover:border-gray-300">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                تعديل
                            </button>

                            {{-- صلاحيات --}}
                            @if(!$isAdm)
                            <a href="{{ route('users.permissions', $user) }}"
                               title="إدارة الصلاحيات"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition border border-purple-200 text-purple-700 hover:bg-purple-50 hover:border-purple-300">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                صلاحيات
                            </a>
                            @endif

                            {{-- كشف حساب --}}
                            <a href="{{ route('users.statement', $user) }}"
                               title="كشف حساب"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition border border-blue-200 text-blue-700 hover:bg-blue-50 hover:border-blue-300">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                كشف حساب
                            </a>

                            {{-- رمز الاسترداد --}}
                            <form method="POST" action="{{ route('users.regenerateBackupCode', $user) }}" class="inline">
                                @csrf
                                <button type="submit"
                                        title="تجديد رمز الاسترداد"
                                        onclick="event.preventDefault(); if(confirm('تجديد رمز الاسترداد لـ {{ addslashes($user->name) }}؟\nالرمز الحالي سيصبح غير صالح.')) this.closest('form').submit();"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-amber-600 border border-amber-200 hover:bg-amber-50 hover:border-amber-300 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                    </svg>
                                </button>
                            </form>

                            {{-- تفعيل / تعطيل --}}
                            @if(!$isMe)
                            <form method="POST" action="{{ route('users.toggle', $user) }}" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        title="{{ $user->is_active ? 'تعطيل الحساب' : 'تفعيل الحساب' }}"
                                        onclick="event.preventDefault(); if(confirm('{{ $user->is_active ? 'تعطيل' : 'تفعيل' }} حساب {{ addslashes($user->name) }}؟')) this.closest('form').submit();"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg transition border
                                               {{ $user->is_active
                                                   ? 'text-red-600 border-red-200 hover:bg-red-50 hover:border-red-300'
                                                   : 'text-green-600 border-green-200 hover:bg-green-50 hover:border-green-300' }}">
                                    @if($user->is_active)
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                    @else
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    @endif
                                </button>
                            </form>
                            @endif

                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-14 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <p class="text-gray-400 font-medium">لا يوجد مستخدمون</p>
                            <p class="text-gray-300 text-xs">جرّب تغيير معايير البحث</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
        <x-pagination-info :items="$users" />
        {{ $users->links() }}
    </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════ MODALS ═══ --}}

{{-- Backup Code Modal --}}
<div x-show="backupCodeModal" x-cloak
     class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-7 text-center">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-5" style="background:#e8f0f7;">
            <svg class="w-8 h-8" style="color:#0F4C75;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
        </div>
        <h3 class="font-bold text-gray-900 text-xl mb-1">رمز الاسترداد</h3>
        <p class="text-sm text-gray-500 mb-5">رمز الاسترداد الخاص بـ <span x-text="backupCodeUser" class="font-semibold text-gray-800"></span></p>
        <div class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-xl p-5 mb-5">
            <p class="font-mono text-2xl font-bold tracking-[0.3em] text-gray-900" x-text="backupCode"></p>
        </div>
        <div class="flex items-start gap-2 bg-red-50 border border-red-100 rounded-lg p-3 mb-5 text-right">
            <svg class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <p class="text-xs text-red-600">احتفظ بهذا الرمز في مكان آمن. لن يُعرض مجدداً بعد إغلاق هذه النافذة.</p>
        </div>
        <button @click="backupCodeModal=false"
                class="w-full text-white py-3 rounded-xl font-semibold text-sm transition hover:opacity-90"
                style="background:#0F4C75;">
            حسناً، تم الحفظ
        </button>
    </div>
</div>

{{-- Add User Modal --}}
<div x-show="addModal" x-cloak
     class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
     @click.self="addModal=false"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg flex flex-col" style="max-height:90vh;">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:#e8f0f7;">
                    <svg class="w-4 h-4" style="color:#0F4C75;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900">إضافة مستخدم جديد</h3>
            </div>
            <button @click="addModal=false" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('users.store') }}" class="p-6 space-y-4 overflow-y-auto flex-1"
              x-data="{ showPwd: false }" autocomplete="off">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">الاسم الكامل <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required placeholder="محمد أحمد..." autocomplete="off"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">اسم الدخول <span class="text-red-500">*</span></label>
                    <input type="text" name="username" required placeholder="username" autocomplete="off"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition font-mono"
                           dir="ltr">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">كلمة المرور <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input :type="showPwd ? 'text' : 'password'" name="password" required minlength="8" placeholder="••••••••" autocomplete="new-password"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pr-10 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition"
                               dir="ltr">
                        <button type="button" @click="showPwd = !showPwd"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition">
                            <svg x-show="!showPwd" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPwd" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">رقم الجوال</label>
                    <input type="text" name="phone" placeholder="07XXXXXXXX"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">الدور</label>
                    <select name="role"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
                        <option value="receptionist" selected>موظف استقبال (افتراضي)</option>
                        @foreach($roles as $role)
                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="pt-2 flex gap-3">
                <button type="submit"
                        class="flex-1 text-white py-3 rounded-xl font-semibold transition text-sm hover:opacity-90"
                        style="background:#0F4C75;">
                    إنشاء المستخدم
                </button>
                <button type="button" @click="addModal=false"
                        class="px-5 py-3 border border-gray-200 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Edit User Modal --}}
<div x-show="editModal" x-cloak
     class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
     @click.self="editModal=false"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-amber-50">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900">تعديل بيانات المستخدم</h3>
            </div>
            <button @click="editModal=false" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form :action="`/users/${editUser.id}`" method="POST" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="_method" value="PUT">

            <div class="bg-gray-50 rounded-xl p-4 flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-base flex-shrink-0"
                     style="background:#0F4C75;" x-text="editUser.name ? editUser.name.charAt(0) : '?'"></div>
                <div>
                    <p class="font-semibold text-gray-800" x-text="editUser.name"></p>
                    <p class="text-xs text-gray-400" x-text="editUser.role"></p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">الاسم الكامل <span class="text-red-500">*</span></label>
                <input type="text" name="name" :value="editUser.name" required
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">رقم الجوال</label>
                <input type="text" name="phone" :value="editUser.phone"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">الدور</label>
                <select name="role"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition bg-white">
                    @foreach($roles as $role)
                    <option :selected="editUser.role === '{{ $role->name }}'" value="{{ $role->name }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">كلمة مرور جديدة</label>
                <input type="password" name="password" minlength="8" placeholder="اتركها فارغة لعدم التغيير"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition"
                       dir="ltr">
                <p class="text-xs text-gray-400 mt-1">8 أحرف كحد أدنى</p>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="flex-1 text-white py-3 rounded-xl font-semibold transition text-sm hover:opacity-90"
                        style="background:#0F4C75;">
                    حفظ التغييرات
                </button>
                <button type="button" @click="editModal=false"
                        class="px-5 py-3 border border-gray-200 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
                    إلغاء
                </button>
            </div>
        </form>
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
     class="fixed bottom-5 left-1/2 -translate-x-1/2 z-[60] flex items-center gap-3 px-5 py-3 rounded-xl text-white text-sm font-medium shadow-xl"
     :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'">
    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path x-show="toast.type === 'success'" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        <path x-show="toast.type === 'error'" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
    </svg>
    <span x-text="toast.message"></span>
</div>

</div>
@endsection
