{{--
    اختيار الوعاء المالي الذي يدخله المبلغ.

    طريقة الدفع وحدها لا تكفي حين يكون للفندق أكثر من حساب بنكي أو أكثر من جهاز
    شبكة: «تحويل بنكي» لا تقول إلى أي حساب. هذا المُنتقي يظهر عند اختيار طريقة
    غير نقدية، ويسرد الأوعية المناسبة لها وحدها.

    المتغيرات:
      $methodModel — تعبير Alpine الذي يحمل طريقة الدفع (مثال: 'method')
      $name        — اسم الحقل (افتراضياً payment_account_id)
      $compact     — نسخة مصغّرة داخل النوافذ المنبثقة
--}}
@php
    $pickerName    = $name ?? 'payment_account_id';
    $pickerCompact = $compact ?? false;
    $pickerAccounts = \App\Models\PaymentAccount::active()->ordered()->get()
        ->groupBy('type')
        ->map(fn ($group) => $group->map(fn ($a) => [
            'id'        => $a->id,
            'name'      => $a->name,
            'bank'      => $a->bank_name,
            'default'   => (bool) $a->is_default,
        ])->values());
    $pickerByMethod = [];
    foreach (\App\Models\PaymentAccount::METHOD_TYPES as $method => $types) {
        $pickerByMethod[$method] = collect($types)
            ->flatMap(fn ($t) => $pickerAccounts->get($t, collect()))
            ->values()->all();
    }
@endphp

<div x-data="{ accounts: {{ Js::from($pickerByMethod) }} }"
     x-show="(accounts[{{ $methodModel }}] || []).length > 0 && {{ $methodModel }} !== 'cash'"
     x-cloak
     class="{{ $pickerCompact ? '' : 'md:col-span-2' }}">
    <label class="block text-xs font-semibold text-gray-600 mb-1.5">
        إلى أي حساب؟ <span class="text-red-500">*</span>
    </label>
    <select name="{{ $pickerName }}"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none bg-white">
        <template x-for="account in (accounts[{{ $methodModel }}] || [])" :key="account.id">
            <option :value="account.id" :selected="account.default"
                    x-text="account.name + (account.bank ? ' — ' + account.bank : '')"></option>
        </template>
    </select>
    <p class="text-[11px] text-gray-400 mt-1">
        يحدّد أين يُسجَّل المبلغ فعلاً، فيظهر في رصيد ذلك الحساب لا في درج الوردية.
    </p>
</div>
