@extends('layouts.app')
@section('title', 'الصناديق والحسابات البنكية')
@section('page-title', 'الصناديق والحسابات البنكية')

@section('content')
<div dir="rtl" class="space-y-5" x-data="{ editing: null, adding: false }">

{{-- إجمالي ما بحوزة الفندق مفصّلاً على أوعيته --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <div class="text-xs text-gray-500">إجمالي ما بحوزة الفندق (نقداً وبنكياً)</div>
            <div class="text-3xl font-black mt-1" style="color:#0F4C75;">{{ number_format($totalOnHand, 0) }} <span class="text-base font-normal text-gray-400">ر.ي</span></div>
        </div>
        @can('settings.manage')
        <button type="button" @click="adding = !adding"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white text-sm font-semibold transition" style="background:#0F4C75;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            إضافة حساب بنكي أو صندوق
        </button>
        @endcan
    </div>
    <p class="text-xs text-gray-500 mt-3 leading-relaxed">
        رصيد كل وعاء يُقرأ من دفتر الأستاذ، فيشمل كل ما دخله وخرج منه — قبضاً من النزلاء،
        صرفاً للمصروفات، تسويةً بين الصندوق والورديات — لا مجموع الدفعات وحده.
    </p>
</div>

{{-- إضافة وعاء جديد --}}
@can('settings.manage')
<form x-show="adding" x-cloak method="POST" action="{{ route('payment-accounts.store') }}"
      class="bg-white rounded-xl shadow-sm border border-blue-200 p-5 space-y-4">
    @csrf
    <h3 class="font-bold text-gray-800 text-sm">وعاء مالي جديد</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">الاسم *</label>
            <input type="text" name="name" required maxlength="120" placeholder="مثال: بنك الكريمي — الجاري"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">النوع *</label>
            <select name="type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none bg-white">
                @foreach(\App\Models\PaymentAccount::TYPES as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">اسم البنك</label>
            <input type="text" name="bank_name" maxlength="120" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">رقم الحساب</label>
            <input type="text" name="account_number" maxlength="60" dir="ltr" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">نسبة عمولة الشبكة (%)</label>
            <input type="number" name="commission_rate" step="0.01" min="0" max="100" placeholder="0"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
        </div>
        <div class="flex items-end">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_default" value="1" class="w-4 h-4 accent-blue-600">
                الوسيلة الافتراضية لهذا النوع
            </label>
        </div>
    </div>
    <div class="flex gap-3">
        <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-bold transition">حفظ</button>
        <button type="button" @click="adding = false" class="px-5 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50">إلغاء</button>
    </div>
    <p class="text-xs text-gray-500">
        يُنشأ للوعاء حسابٌ في دليل الحسابات تلقائياً تحت أبيه الصحيح — لا حاجة لتحرير الدليل يدوياً.
    </p>
</form>
@endcan

{{-- فلترة فترة الحركة --}}
<form method="GET" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
    <div class="flex flex-wrap gap-3 items-end">
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">حركة من</label>
            <input type="date" name="from" value="{{ $from }}" onchange="this.form.submit()"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none bg-white">
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">إلى</label>
            <input type="date" name="to" value="{{ $to }}" onchange="this.form.submit()"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none bg-white">
        </div>
        <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm self-end" style="background:#0F4C75;">عرض</button>
    </div>
</form>

{{-- الأوعية --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    @foreach($accounts as $account)
    @php
        $movement = $movements[$account->account_code] ?? null;
        $typeStyles = [
            'shift_cash' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'text' => 'text-emerald-700', 'icon' => '💵'],
            'safe'       => ['bg' => 'bg-amber-50',   'border' => 'border-amber-200',   'text' => 'text-amber-700',   'icon' => '🏦'],
            'bank'       => ['bg' => 'bg-blue-50',    'border' => 'border-blue-200',    'text' => 'text-blue-700',    'icon' => '🏛️'],
            'pos'        => ['bg' => 'bg-purple-50',  'border' => 'border-purple-200',  'text' => 'text-purple-700',  'icon' => '💳'],
        ][$account->type] ?? ['bg' => 'bg-gray-50', 'border' => 'border-gray-200', 'text' => 'text-gray-700', 'icon' => '📦'];
    @endphp
    <div class="bg-white rounded-xl shadow-sm border {{ $account->is_active ? $typeStyles['border'] : 'border-gray-200 opacity-60' }} overflow-hidden">
        <div class="px-5 py-3 {{ $typeStyles['bg'] }} flex items-center justify-between gap-2">
            <div class="flex items-center gap-2 min-w-0">
                <span class="text-lg leading-none">{{ $typeStyles['icon'] }}</span>
                <div class="min-w-0">
                    <div class="font-bold text-gray-800 text-sm truncate">{{ $account->name }}</div>
                    <div class="text-[11px] {{ $typeStyles['text'] }}">
                        {{ $account->type_label }}
                        <span class="text-gray-400 font-mono">· {{ $account->account_code }}</span>
                        @if($account->is_default)<span class="font-bold">· افتراضي</span>@endif
                    </div>
                </div>
            </div>
            @unless($account->is_active)
            <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-200 text-gray-600 font-bold flex-shrink-0">معطّل</span>
            @endunless
        </div>

        <div class="p-5">
            <div class="text-xs text-gray-500">الرصيد الحالي</div>
            <div class="text-2xl font-black {{ $account->balance < 0 ? 'text-red-600' : 'text-gray-800' }}">
                {{ number_format($account->balance, 0) }} <span class="text-sm font-normal text-gray-400">{{ $account->currency }}</span>
            </div>

            @if($account->bank_name || $account->account_number)
            <div class="text-xs text-gray-500 mt-2">
                {{ $account->bank_name }}
                @if($account->account_number)<span class="font-mono" dir="ltr">· {{ $account->account_number }}</span>@endif
            </div>
            @endif

            <div class="grid grid-cols-2 gap-3 mt-4 pt-4 border-t border-gray-100">
                <div>
                    <div class="text-[11px] text-gray-400">داخل في الفترة</div>
                    <div class="text-sm font-bold text-green-600">{{ number_format($movement->total_in ?? 0, 0) }}</div>
                </div>
                <div>
                    <div class="text-[11px] text-gray-400">خارج في الفترة</div>
                    <div class="text-sm font-bold text-red-600">{{ number_format($movement->total_out ?? 0, 0) }}</div>
                </div>
            </div>

            {{-- الصرف يُسجَّل من نموذج المصروف الواحد، ويُفتح هنا جاهزاً على هذا
                 الصندوق — مدخلٌ ثانٍ لا نموذجٌ ثانٍ --}}
            @can('expenses.create')
            @if($account->is_active && $account->type !== 'pos')
            <a href="{{ route('expenses.create', [
                    'payment_account_id' => $account->id,
                    'payment_method'     => $account->is_cash ? 'cash' : 'bank_transfer',
               ]) }}"
               class="mt-4 w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-white transition"
               style="background:#0F4C75;">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7"/></svg>
                صرف مصروف من هذا الصندوق
            </a>
            @endif
            @endcan

            @can('settings.manage')
            <div class="flex items-center gap-2 mt-4">
                <button type="button" @click="editing = editing === {{ $account->id }} ? null : {{ $account->id }}"
                        class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition">تعديل</button>
                <form method="POST" action="{{ route('payment-accounts.toggle', $account) }}"
                      onsubmit="return confirm('{{ $account->is_active ? 'تعطيل هذا الوعاء؟ لن يظهر في شاشات الدفع.' : 'تفعيل هذا الوعاء؟' }}')">
                    @csrf @method('PATCH')
                    <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 {{ $account->is_active ? 'text-red-600' : 'text-green-600' }} hover:bg-gray-50 transition">
                        {{ $account->is_active ? 'تعطيل' : 'تفعيل' }}
                    </button>
                </form>
            </div>

            <form x-show="editing === {{ $account->id }}" x-cloak method="POST"
                  action="{{ route('payment-accounts.update', $account) }}"
                  class="mt-4 pt-4 border-t border-gray-100 space-y-3">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">الاسم</label>
                        <input type="text" name="name" value="{{ $account->name }}" required maxlength="120"
                               class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">اسم البنك</label>
                        <input type="text" name="bank_name" value="{{ $account->bank_name }}" maxlength="120"
                               class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">رقم الحساب</label>
                        <input type="text" name="account_number" value="{{ $account->account_number }}" maxlength="60" dir="ltr"
                               class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">عمولة الشبكة (%)</label>
                        <input type="number" name="commission_rate" value="{{ $account->commission_rate }}" step="0.01" min="0" max="100"
                               class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs outline-none">
                    </div>
                </div>
                <input type="hidden" name="type" value="{{ $account->type }}">
                <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                    <input type="checkbox" name="is_default" value="1" @checked($account->is_default) class="w-3.5 h-3.5 accent-blue-600">
                    الوسيلة الافتراضية لهذا النوع
                </label>
                <button type="submit" class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition">حفظ التعديل</button>
            </form>
            @endcan
        </div>
    </div>
    @endforeach
</div>

</div>
@endsection
