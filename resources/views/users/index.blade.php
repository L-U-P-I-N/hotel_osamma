@extends('layouts.app')
@section('title', 'المستخدمون')
@section('page-title', 'إدارة المستخدمين')

@section('content')
<div x-data="{ addModal: false, editModal: false, editUser: {} }">

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
                        <div class="flex items-center gap-3">
                            <button @click="editUser={{ json_encode(['id'=>$user->id,'name'=>$user->name,'phone'=>$user->phone,'role'=>$user->roles->first()?->name]) }}; editModal=true"
                                    class="text-xs font-medium" style="color:#0F4C75;">تعديل</button>
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.toggle', $user) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs font-medium {{ $user->is_active ? 'text-red-500 hover:text-red-700' : 'text-green-600 hover:text-green-800' }}">
                                    {{ $user->is_active ? 'تعطيل' : 'تفعيل' }}
                                </button>
                            </form>
                            @endif
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
    <div class="px-6 py-4 border-t border-gray-100">{{ $users->links() }}</div>
    @endif
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
        <form method="POST" action="{{ route('users.store') }}" class="p-6 space-y-4">
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
                <input type="password" name="password" required minlength="8" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none">
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

</div>
@endsection
