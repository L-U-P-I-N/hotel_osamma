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
    openDropdown: null,
    toast: { show: false, message: '', type: 'success' }
}" x-init="
    @if(session('new_backup_code'))
    backupCode = '{{ session('new_backup_code') }}';
    backupCodeUser = '{{ session('new_backup_code_user') }}';
    backupCodeModal = true;
    @endif
    @if(session('success'))
    toast.message = '{{ session('success') }}';
    toast.type = 'success';
    toast.show = true;
    setTimeout(() => toast.show = false, 3000);
    @endif
">

<div class="flex justify-between items-center mb-5">
    <p class="text-sm text-gray-500">إجمالي المستخدمين: {{ $users->total() }}</p>
    <div class="flex gap-3">
        <a href="{{ route('audit.log') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition">سجل المراجعة</a>
        <button @click="addModal=true" class="flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm transition" style="background:#0F4C75;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            مستخدم جديد
        </button>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100"><tr>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الموظف</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">اسم المستخدم</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الجوال</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الدور</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إجراءات</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($users as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-sm" style="background:#0F4C75;">
                                {{ mb_substr($user->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="font-medium text-gray-800">{{ $user->name }}</div>
                                <div class="text-xs text-gray-400">{{ $user->employee_id }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 font-mono text-gray-600 text-xs">{{ $user->username }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $user->phone ?: '-' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium" style="background:#e8f0f7; color:#0F4C75;">
                            {{ $user->roles->first()?->name ?? '-' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($user->is_active)
                        <span class="px-2 py-0.5 bg-green-100 text-green-800 rounded-full text-xs">نشط</span>
                        @else
                        <span class="px-2 py-0.5 bg-red-100 text-red-800 rounded-full text-xs">معطّل</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="relative" x-data="{ open{{ $user->id }}: false }" @click.away="open{{ $user->id }}=false">
                            <button @click="open{{ $user->id }}=!open{{ $user->id }}" class="inline-flex items-center justify-center w-8 h-8 text-gray-500 rounded-lg hover:bg-gray-100 transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10.5 1.5H9.5A1.5 1.5 0 008 3v14a1.5 1.5 0 001.5 1.5h1a1.5 1.5 0 001.5-1.5V3a1.5 1.5 0 00-1.5-1.5zM4 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm12 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3z"/></svg>
                            </button>
                            <div x-show="open{{ $user->id }}" x-cloak class="absolute left-0 mt-1 w-40 bg-white rounded-lg shadow-lg border border-gray-200 z-50 py-1">
                                <button @click="editUser={{ json_encode(['id'=>$user->id,'name'=>$user->name,'phone'=>$user->phone,'role'=>$user->roles->first()?->name]) }}; editModal=true; open{{ $user->id }}=false"
                                        class="w-full px-4 py-2 text-right text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    تعديل
                                </button>
                                @if(!$user->isAdmin())
                                <a href="{{ route('users.permissions', $user) }}"
                                   class="w-full px-4 py-2 text-right text-sm text-purple-600 hover:bg-purple-50 flex items-center gap-2 block">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    صلاحيات
                                </a>
                                @endif
                                <form method="POST" action="{{ route('users.regenerateBackupCode', $user) }}" class="block">
                                    @csrf
                                    <button type="submit" class="w-full px-4 py-2 text-right text-sm text-amber-600 hover:bg-amber-50 flex items-center gap-2"
                                            onclick="event.preventDefault();
                                            if(confirm('تجديد رمز الاسترداد لـ {{ addslashes($user->name) }}؟\n\nرمز الاسترداد الحالي سيصبح غير صالح.')) {
                                                this.closest('form').submit();
                                            }">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                        رمز الاسترداد
                                    </button>
                                </form>
                                <div class="border-t border-gray-100 my-1"></div>
                                @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('users.toggle', $user) }}" class="block">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="w-full px-4 py-2 text-right text-sm {{ $user->is_active ? 'text-red-600 hover:bg-red-50' : 'text-green-600 hover:bg-green-50' }} flex items-center gap-2"
                                            onclick="event.preventDefault();
                                            if(confirm('{{ $user->is_active ? 'تعطيل' : 'تفعيل' }} المستخدم {{ addslashes($user->name) }}؟')) {
                                                this.closest('form').submit();
                                            }">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M{{ $user->is_active ? '6 18L18 6M6 6l12 12' : '9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' }}"/></svg>
                                        {{ $user->is_active ? 'تعطيل' : 'تفعيل' }}
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">لا يوجد مستخدمون</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        <x-pagination-info :items="$users" />
        {{ $users->links() }}
    </div>
    @endif
</div>

<!-- Backup Code Modal -->
<div x-show="backupCodeModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
        <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4" style="background:#e8f0f7;">
            <svg class="w-7 h-7" style="color:#0F4C75;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
        </div>
        <h3 class="font-bold text-gray-800 text-lg mb-1">رمز الاسترداد</h3>
        <p class="text-sm text-gray-500 mb-4">رمز الاسترداد الخاص بـ <span x-text="backupCodeUser" class="font-semibold text-gray-700"></span></p>
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-4">
            <p class="font-mono text-xl font-bold tracking-widest text-gray-800" x-text="backupCode"></p>
        </div>
        <p class="text-xs text-red-600 mb-5">احتفظ بهذا الرمز في مكان آمن. لن يُعرض مجدداً.</p>
        <button @click="backupCodeModal=false" class="w-full text-white py-2.5 rounded-lg font-semibold text-sm" style="background:#0F4C75;">
            حسناً، تم الحفظ
        </button>
    </div>
</div>

<!-- Add User Modal -->
<div x-show="addModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="addModal=false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" style="max-height:90vh; overflow-y:auto;">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white">
            <h3 class="font-bold text-gray-800">إضافة مستخدم جديد</h3>
            <button @click="addModal=false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('users.store') }}" class="p-6 space-y-4" x-data="{ pwd: '', pwd_confirm: '' }">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">الاسم الكامل *</label>
                <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">رقم الموظف *</label>
                <input type="text" name="employee_id" required placeholder="EMP004" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">اسم المستخدم *</label>
                <input type="text" name="username" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">كلمة المرور *</label>
                <input type="password" name="password" x-model="pwd" required minlength="8" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none">
                <p class="text-xs text-gray-500 mt-1">على الأقل 8 أحرف</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">تأكيد كلمة المرور *</label>
                <input type="password" name="password_confirmation" x-model="pwd_confirm" required minlength="8" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none">
                <p class="text-xs text-red-500 mt-1" x-show="pwd && pwd_confirm && pwd !== pwd_confirm">كلمات المرور غير متطابقة</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">رقم الجوال</label>
                <input type="text" name="phone" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">الدور *</label>
                <select name="role" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none">
                    @foreach($roles as $role)
                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="w-full text-white py-2.5 rounded-lg font-semibold transition text-sm" style="background:#0F4C75;">إنشاء المستخدم</button>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div x-show="editModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="editModal=false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-800">تعديل بيانات المستخدم</h3>
            <button @click="editModal=false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form :action="`/users/${editUser.id}`" method="POST" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="_method" value="PUT">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">الاسم الكامل</label>
                <input type="text" name="name" :value="editUser.name" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">رقم الجوال</label>
                <input type="text" name="phone" :value="editUser.phone" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">كلمة مرور جديدة (اتركها فارغة لعدم التغيير)</label>
                <input type="password" name="password" minlength="8" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">الدور</label>
                <select name="role" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none">
                    @foreach($roles as $role)
                    <option :selected="editUser.role === '{{ $role->name }}'" value="{{ $role->name }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="w-full text-white py-2.5 rounded-lg font-semibold transition text-sm" style="background:#0F4C75;">حفظ التغييرات</button>
        </form>
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
