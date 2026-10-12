@extends('layouts.app')
@section('title', 'تسجيل مصروف')
@section('page-title', 'تسجيل مصروف جديد')

@section('content')
<div dir="rtl" class="max-w-xl mx-auto">

<div class="ui-card p-6">
    <h2 class="text-lg font-bold text-gray-800 mb-6">بيانات المصروف</h2>

    <form method="POST" action="{{ route('expenses.store') }}" class="space-y-4">
        @csrf

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
        @endif

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">المبلغ (ر.ي) *</label>
                <input type="number" name="amount" value="{{ old('amount') }}" step="0.01" min="0.01" required
                       class="w-full border @error('amount') border-red-400 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">التاريخ *</label>
                <input type="date" name="expense_date" value="{{ old('expense_date', today()->toDateString()) }}" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
            </div>
        </div>

        @if($shifts->isNotEmpty())
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">الوردية *</label>
            <select name="shift_id" required
                    class="w-full border @error('shift_id') border-red-400 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
                @foreach($shifts as $s)
                <option value="{{ $s->id }}" {{ old('shift_id', $defaultShiftId) == $s->id ? 'selected' : '' }}>
                    {{ $s->shift_date->format('d/m/Y') }}@if(!$s->is_closed) (مفتوحة الآن) @else (مقفلة) @endif
                </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-400 mt-1">اختر الوردية التي يخصّها هذا المصروف فعلياً — ليست بالضرورة الوردية المفتوحة حالياً.</p>
        </div>
        @endif

@php
    // رواتب الشهر تُصرف بعد انتهائه، فالشهر الماضي هو الافتراضي المعقول
    $__lastMonth = now()->subMonthNoOverflow();
    $__defaultSalaryMonth = $__lastMonth->month;
    $__defaultSalaryYear  = $__lastMonth->year;
@endphp
        <div x-data="{ category: '{{ old('category', array_key_first($categories)) }}' }">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">الفئة *</label>
            <select name="category" x-model="category" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
                @foreach($categories as $key => $label)
                <option value="{{ $key }}" {{ old('category')==$key?'selected':'' }}>{{ $label }}</option>
                @endforeach
            </select>

            {{--
                شهر الراتب مستقلّ عن تاريخ الصرف: رواتب الشهر تُصرف بعد انتهائه،
                فراتب سبتمبر يُصرف في أكتوبر. والافتراضي الشهر الماضي لا الحالي،
                فهو الغالب. وبدونه كان الخصم يقع على الشهر الذي نحن فيه.
            --}}
            <div x-show="category === 'salary'" x-cloak class="mt-3 bg-blue-50 border border-blue-200 rounded-lg p-3">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">هذا الراتب عن أي شهر؟ *</label>
                <div class="flex gap-2">
                    <select name="salary_month" :disabled="category !== 'salary'"
                            class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 bg-white">
                        @for($__m = 1; $__m <= 12; $__m++)
                        <option value="{{ $__m }}" @selected((int) old('salary_month', $__defaultSalaryMonth) === $__m)>{{ \App\Models\Salary::monthName($__m) }}</option>
                        @endfor
                    </select>
                    <select name="salary_year" :disabled="category !== 'salary'"
                            class="w-28 border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 bg-white">
                        @for($__y = now()->year + 1; $__y >= now()->year - 3; $__y--)
                        <option value="{{ $__y }}" @selected((int) old('salary_year', $__defaultSalaryYear) === $__y)>{{ $__y }}</option>
                        @endfor
                    </select>
                </div>
                <p class="text-xs text-blue-800 mt-2 leading-relaxed">
                    يُخصم المبلغ من راتب هذا الشهر لا من شهر تاريخ الصرف.
                    ولقسيمة راتب كاملة (أساسي وخصومات وصافي) استخدم
                    <a href="{{ route('salaries.index') }}" class="underline font-semibold">صفحة الرواتب</a>.
                </p>
            </div>
        </div>

        {{-- مصدر المال: طريقة الدفع ثم الوعاء الذي يخرج منه فعلاً --}}
        @php
            // تُمرَّران من بطاقة الصندوق في صفحة «الصناديق والبنوك»، فيفتح النموذج
            // جاهزاً على ذلك الصندوق بدل أن يبحث عنه الموظف
            $__prefillAccount = request('payment_account_id');
            $__prefillMethod  = request('payment_method', $__expenseMethod ?? 'cash');
        @endphp
        <div x-data="{ method: '{{ old('payment_method', $__prefillMethod) }}' }" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">طريقة الدفع *</label>
                <select name="payment_method" x-model="method" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400 bg-white">
                    <option value="cash">نقداً</option>
                    <option value="bank_transfer">تحويل بنكي</option>
                    <option value="later">لاحقاً (يُقيَّد ذمةً على الفندق)</option>
                </select>
            </div>

            {{-- الأوعية النقدية تُسرد يدوياً: المُنتقي المشترك يُخفي نفسه عند «نقداً»
                 لأنه مصمَّم لمقبوضات النزلاء، والمصروف يخرج من درجٍ أو خزنة --}}
            @php
                $__cashAccounts = \App\Models\PaymentAccount::active()
                    ->whereIn('type', ['shift_cash', 'safe'])->ordered()->get();
                $__safeIds = $__cashAccounts->where('type', 'safe')->pluck('id')->values();
            @endphp
            {{--
                :disabled لا زينة: الحقلان يحملان نفس الاسم payment_account_id وكلاهما
                في الصفحة. وx-show يُخفي بالعرض لا بالتعطيل، فالمخفيّ كان يُرسَل أيضاً
                ويغلب الظاهر (آخر حقل بنفس الاسم يفوز في PHP) — فمصروف «نقداً من الخزنة»
                كان يُرسل معرّف حسابٍ بنكي، فيسقط الاختيار إلى الدرج الافتراضي ويظهر
                الموظف عاجزاً بمبلغ لم يخرج من يده.
            --}}
            <div x-show="method === 'cash'" x-cloak
                 x-data="{ safeIds: @js($__safeIds), account: '{{ old('payment_account_id', $__prefillAccount ?: $__cashAccounts->first()?->id) }}' }">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">من أي صندوق؟</label>
                <select name="payment_account_id" x-model="account" :disabled="method !== 'cash'"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400 bg-white">
                    @foreach($__cashAccounts as $__acc)
                    <option value="{{ $__acc->id }}" @selected(old('payment_account_id', $__prefillAccount) == $__acc->id)>{{ $__acc->name }}</option>
                    @endforeach
                </select>

                {{-- الرسالة تتبع الوعاء المختار: كانت تَعِد بالخصم حتى حين يُصرف من
                     خزنة الفندق، فيظنّ الموظف أن المبلغ سيُحمَّل عليه --}}
                <p x-show="!safeIds.includes(Number(account))" x-cloak
                   class="mt-2 bg-green-50 border border-green-200 rounded-lg p-3 text-xs text-green-700">
                    سيُخصم هذا المبلغ تلقائياً من نقدية ورديتك اليوم.
                </p>
                <p x-show="safeIds.includes(Number(account))" x-cloak
                   class="mt-2 bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-800">
                    لن يُخصم من نقدية ورديتك — المال يخرج من خزنة الفندق.
                    يُسجَّل ويظهر في تصدير وردية الموظف بأنه صُرف من الصندوق العام.
                </p>
            </div>

            <div x-show="method === 'bank_transfer'" x-cloak>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">من أي حساب بنكي؟</label>
                <select name="payment_account_id" :disabled="method !== 'bank_transfer'"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400 bg-white">
                    @foreach(\App\Models\PaymentAccount::active()->where('type', 'bank')->ordered()->get() as $__acc)
                    <option value="{{ $__acc->id }}" @selected(old('payment_account_id', $__prefillAccount) == $__acc->id)>{{ $__acc->name }}{{ $__acc->bank_name ? ' — ' . $__acc->bank_name : '' }}</option>
                    @endforeach
                </select>
            </div>

            <p x-show="method === 'later'" x-cloak class="text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                لن يخرج مال الآن — يُسجَّل المصروف ذمةً على الفندق تُسدَّد لاحقاً من صفحة «المصروفات المؤجّلة».
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">صرف لموظف في الفندق؟</label>
            <select name="employee_id" id="employee_select"
                    onchange="var o=this.options[this.selectedIndex]; var r=document.getElementById('recipient_field'); if(this.value){ r.value=o.dataset.name; } document.getElementById('employee_hint').classList.toggle('hidden', !this.value);"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
                <option value="">— لا، مستلم خارجي —</option>
                @foreach($employees as $emp)
                <option value="{{ $emp->id }}" data-name="{{ $emp->name }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                    {{ $emp->name }} — {{ $emp->position }}
                </option>
                @endforeach
            </select>
            <p id="employee_hint" class="{{ old('employee_id') ? '' : 'hidden' }} text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-2 mt-1.5">
                سيُقيَّد هذا المبلغ كمسحوبات على الموظف ويُخصم تلقائياً من راتبه الشهري.
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">اسم المستلم *</label>
            <input type="text" name="recipient_name" id="recipient_field" value="{{ old('recipient_name') }}" placeholder="اسم الشخص الذي صُرف له المبلغ" required
                   class="w-full border @error('recipient_name') border-red-400 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-400">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">الوصف / الملاحظات</label>
            <textarea name="description" rows="3" placeholder="وصف تفصيلي للمصروف..."
                      class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none resize-none focus:border-blue-400">{{ old('description') }}</textarea>
        </div>


        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 text-white rounded-lg text-sm font-semibold transition" style="background:var(--ui-accent);color:var(--ui-accent-fg);">
                تسجيل المصروف
            </button>
            <a href="{{ route('expenses.index') }}" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition">
                إلغاء
            </a>
        </div>
    </form>
</div>

</div>
@endsection
