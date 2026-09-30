@extends('layouts.app')
@section('title', 'الصناديق والحسابات البنكية')
@section('page-title', 'الصناديق والحسابات البنكية')

@section('content')
<div dir="rtl" x-data="{ editing: null, adding: false }">

    <div class="ui-head">
        <h2 class="ui-head-title">الصناديق والحسابات البنكية</h2>
        <p class="ui-head-meta">
            إجمالي ما بحوزة الفندق نقداً وبنكياً:
            <b>{{ number_format($totalOnHand, 0) }}</b> ر.ي
        </p>
        @can('settings.manage')
        <div class="ui-head-actions">
            <button type="button" class="ui-btn ui-btn--primary" @click="adding = !adding">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                إضافة حساب بنكي أو صندوق
            </button>
        </div>
        @endcan
    </div>

    <p class="ui-note" style="margin-bottom:.75rem;">
        رصيد كل وعاء يُقرأ من دفتر الأستاذ، فيشمل كل ما دخله وخرج منه — قبضاً من النزلاء،
        صرفاً للمصروفات، تسويةً بين الصندوق والورديات — لا مجموع الدفعات وحده.
    </p>

    {{-- وعاء جديد --}}
    @can('settings.manage')
    <form x-show="adding" x-cloak method="POST" action="{{ route('payment-accounts.store') }}" class="ui-panel">
        @csrf
        <div class="ui-panel-head"><span class="ui-panel-title">وعاء مالي جديد</span></div>
        <div class="ui-panel-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(12rem,1fr));gap:.75rem;">
                <div class="ui-field" style="margin:0;">
                    <label for="pa-name">الاسم <span class="req">*</span></label>
                    <input id="pa-name" type="text" name="name" required maxlength="120"
                           class="ui-input" placeholder="مثال: بنك الكريمي — الجاري">
                </div>
                <div class="ui-field" style="margin:0;">
                    <label for="pa-type">النوع <span class="req">*</span></label>
                    <select id="pa-type" name="type" required class="ui-input">
                        @foreach(\App\Models\PaymentAccount::TYPES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ui-field" style="margin:0;">
                    <label for="pa-bank">اسم البنك</label>
                    <input id="pa-bank" type="text" name="bank_name" maxlength="120" class="ui-input">
                </div>
                <div class="ui-field" style="margin:0;">
                    <label for="pa-acc">رقم الحساب</label>
                    <input id="pa-acc" type="text" name="account_number" maxlength="60" dir="ltr" class="ui-input is-mono">
                </div>
                <div class="ui-field" style="margin:0;">
                    <label for="pa-comm">نسبة عمولة الشبكة (%)</label>
                    <input id="pa-comm" type="number" name="commission_rate" step="0.01" min="0" max="100"
                           placeholder="0" class="ui-input is-mono">
                </div>
                <div style="display:flex;align-items:flex-end;">
                    <label class="ui-toggle">
                        <input type="checkbox" name="is_default" value="1">
                        الوسيلة الافتراضية لهذا النوع
                    </label>
                </div>
            </div>
            <p class="ui-hint" style="margin-top:.625rem;">
                يُنشأ للوعاء حسابٌ في دليل الحسابات تلقائياً تحت أبيه الصحيح — لا حاجة لتحرير الدليل يدوياً.
            </p>
        </div>
        <div class="ui-actions">
            <button type="submit" class="ui-btn ui-btn--primary">حفظ</button>
            <button type="button" class="ui-btn" @click="adding = false">إلغاء</button>
        </div>
    </form>
    @endcan

    {{-- فترة الحركة --}}
    <form method="GET" class="ui-bar">
        <label class="inline" for="pa-from">حركة من</label>
        <input id="pa-from" type="date" name="from" value="{{ $from }}" onchange="this.form.submit()">
        <label class="inline" for="pa-to">إلى</label>
        <input id="pa-to" type="date" name="to" value="{{ $to }}" onchange="this.form.submit()">
        <button type="submit" class="ui-btn">عرض</button>
    </form>

    {{-- الأوعية --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(20rem,1fr));gap:.75rem;">
        @foreach($accounts as $account)
        @php
            $movement = $movements[$account->account_code] ?? null;
            // لون النوع يُحمل في شريط رفيع وفي الكود، لا في خلفية كاملة تُتعب العين
            $tone = [
                'shift_cash' => 'var(--ui-revenue)',
                'safe'       => 'var(--ui-expense)',
                'bank'       => 'var(--ui-asset)',
                'pos'        => 'var(--ui-equity)',
            ][$account->type] ?? 'var(--ui-ink-3)';
        @endphp

        <div class="ui-panel" style="margin:0;{{ $account->is_active ? '' : 'opacity:.6;' }}">
            <div class="ui-panel-head" style="border-top:2px solid {{ $tone }};">
                <div style="min-width:0;flex:1;">
                    <div class="t-strong" style="font-size:.8125rem;color:var(--ui-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ $account->name }}
                    </div>
                    <div style="font-size:.6875rem;color:var(--ui-ink-3);margin-top:.0625rem;">
                        <span style="color:{{ $tone }};font-weight:700;">{{ $account->type_label }}</span>
                        <span class="code">· {{ $account->account_code }}</span>
                        @if($account->is_default)<span style="font-weight:700;"> · افتراضي</span>@endif
                    </div>
                </div>
                @unless($account->is_active)
                <span class="ui-chip">معطّل</span>
                @endunless
            </div>

            <div class="ui-panel-body">
                <div style="font-size:.625rem;color:var(--ui-ink-3);">الرصيد الحالي</div>
                <div class="num num--lg {{ $account->balance < 0 ? 'num--neg' : '' }}"
                     style="font-size:1.5rem;text-align:start;">
                    {{ number_format($account->balance, 0) }}
                    <span style="font-size:.75rem;font-weight:400;color:var(--ui-ink-3);">{{ $account->currency }}</span>
                </div>

                @if($account->bank_name || $account->account_number)
                <div style="font-size:.6875rem;color:var(--ui-ink-3);margin-top:.375rem;">
                    {{ $account->bank_name }}
                    @if($account->account_number)<span class="code" dir="ltr">· {{ $account->account_number }}</span>@endif
                </div>
                @endif

                <dl style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-top:.75rem;padding-top:.625rem;border-top:1px solid var(--ui-line-soft);">
                    <div>
                        <dt style="font-size:.625rem;color:var(--ui-ink-3);">داخل في الفترة</dt>
                        <dd class="num num--pos" style="text-align:start;font-weight:700;">{{ number_format($movement->total_in ?? 0, 0) }}</dd>
                    </div>
                    <div>
                        <dt style="font-size:.625rem;color:var(--ui-ink-3);">خارج في الفترة</dt>
                        <dd class="num num--neg" style="text-align:start;font-weight:700;">{{ number_format($movement->total_out ?? 0, 0) }}</dd>
                    </div>
                </dl>

                {{-- الصرف يُسجَّل من نموذج المصروف الواحد، ويُفتح هنا جاهزاً على هذا
                     الصندوق — مدخلٌ ثانٍ لا نموذجٌ ثانٍ --}}
                @can('expenses.create')
                @if($account->is_active && $account->type !== 'pos')
                <a href="{{ route('expenses.create', [
                        'payment_account_id' => $account->id,
                        'payment_method'     => $account->is_cash ? 'cash' : 'bank_transfer',
                   ]) }}" class="ui-btn ui-btn--primary" style="width:100%;margin-top:.75rem;">
                    صرف مصروف من هذا الصندوق
                </a>
                @endif
                @endcan
            </div>

            @can('settings.manage')
            <div class="ui-actions">
                <button type="button" class="ui-btn ui-btn--sm"
                        @click="editing = editing === {{ $account->id }} ? null : {{ $account->id }}">تعديل</button>
                <form method="POST" action="{{ route('payment-accounts.toggle', $account) }}" style="display:inline;"
                      onsubmit="return confirm('{{ $account->is_active ? 'تعطيل هذا الوعاء؟ لن يظهر في شاشات الدفع.' : 'تفعيل هذا الوعاء؟' }}')">
                    @csrf @method('PATCH')
                    <button type="submit" class="ui-btn ui-btn--sm {{ $account->is_active ? 'ui-btn--danger' : '' }}">
                        {{ $account->is_active ? 'تعطيل' : 'تفعيل' }}
                    </button>
                </form>
            </div>

            <form x-show="editing === {{ $account->id }}" x-cloak method="POST"
                  action="{{ route('payment-accounts.update', $account) }}"
                  class="ui-panel-body" style="border-top:1px solid var(--ui-line);">
                @csrf @method('PUT')
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.625rem;">
                    <div class="ui-field" style="margin:0;">
                        <label>الاسم</label>
                        <input type="text" name="name" value="{{ $account->name }}" required maxlength="120" class="ui-input">
                    </div>
                    <div class="ui-field" style="margin:0;">
                        <label>اسم البنك</label>
                        <input type="text" name="bank_name" value="{{ $account->bank_name }}" maxlength="120" class="ui-input">
                    </div>
                    <div class="ui-field" style="margin:0;">
                        <label>رقم الحساب</label>
                        <input type="text" name="account_number" value="{{ $account->account_number }}"
                               maxlength="60" dir="ltr" class="ui-input is-mono">
                    </div>
                    <div class="ui-field" style="margin:0;">
                        <label>عمولة الشبكة (%)</label>
                        <input type="number" name="commission_rate" value="{{ $account->commission_rate }}"
                               step="0.01" min="0" max="100" class="ui-input is-mono">
                    </div>
                </div>
                <input type="hidden" name="type" value="{{ $account->type }}">
                <label class="ui-toggle" style="margin-top:.625rem;">
                    <input type="checkbox" name="is_default" value="1" @checked($account->is_default)>
                    الوسيلة الافتراضية لهذا النوع
                </label>
                <button type="submit" class="ui-btn ui-btn--primary" style="width:100%;margin-top:.625rem;">حفظ التعديل</button>
            </form>
            @endcan
        </div>
        @endforeach
    </div>

</div>
@endsection
