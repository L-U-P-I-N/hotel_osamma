@extends('layouts.app')
@section('title', 'المصروفات')
@section('page-title', 'إدارة المصروفات')

@section('content')
<div dir="rtl">

    <div class="ui-head">
        <h2 class="ui-head-title">المصروفات</h2>
        <p class="ui-head-meta">
            <b>{{ number_format($stats['count']) }}</b> عملية بإجمالي
            <b>{{ number_format($stats['total'], 0) }}</b> ر.ي
        </p>
        <div class="ui-head-actions">
            <a href="{{ route('expenses.pdf', request()->query()) }}" class="ui-btn">PDF</a>
            <a href="{{ route('expenses.excel', request()->query()) }}" class="ui-btn">Excel</a>
            @can('expenses.create')
            <a href="{{ route('expenses.create') }}" class="ui-btn ui-btn--primary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                تسجيل مصروف
            </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
    <div class="ui-note ui-note--ok" style="margin-bottom:.75rem;">{{ session('success') }}</div>
    @endif

    <dl class="ui-stats">
        <div class="ui-stat"><dt>إجمالي المصروفات</dt><dd class="num--neg">{{ number_format($stats['total'], 0) }}</dd></div>
        <div class="ui-stat"><dt>عدد المصروفات</dt><dd>{{ number_format($stats['count']) }}</dd></div>
        <div class="ui-stat"><dt>المتوسط</dt><dd>{{ number_format($stats['average'], 0) }}</dd></div>
        <div class="ui-stat"><dt>الأقل</dt><dd>{{ number_format($stats['min'], 0) }}</dd></div>
        <div class="ui-stat"><dt>الأعلى</dt><dd>{{ number_format($stats['max'], 0) }}</dd></div>
    </dl>

    {{--
        التوزيع حسب الفئة: أعمدة مرتّبة تنازلياً بلون واحد، لا دائرةً ملوّنة.
        المطلوب هنا مقارنة مقادير، والعين تقارن الأطوال ولا تقارن زوايا القطاعات —
        وكانت الدائرة مصحوبةً بقائمة الأرقام نفسها، فالعمود يجمع الاثنين في مكان
        واحد. ولأن السلسلة واحدة فلا حاجة لألوان تصنيفية أصلاً، فيصحّ في الوضعين
        الليلي والنهاري بلا استثناءات.
    --}}
    @if($byCategory->count() > 0)
    @php $maxCat = max($byCategory->pluck('total')->all() ?: [1]); @endphp
    <div class="ui-panel">
        <div class="ui-panel-head"><span class="ui-panel-title">التوزيع حسب الفئة</span></div>
        <div class="ui-panel-body">
            <ul style="list-style:none;margin:0;padding:0;display:grid;gap:.5rem;">
                @foreach($byCategory->sortByDesc('total') as $cat => $data)
                @php $pct = $maxCat > 0 ? round($data['total'] / $maxCat * 100) : 0; @endphp
                <li>
                    <div style="display:flex;align-items:baseline;gap:.5rem;font-size:.75rem;margin-bottom:.1875rem;">
                        <span style="color:var(--ui-ink);">{{ $categories[$cat] ?? $cat }}</span>
                        <span class="num" style="margin-inline-start:auto;font-weight:700;">{{ number_format($data['total'], 0) }}</span>
                        <span class="num num--zero" style="min-width:2.5rem;">{{ $pct }}%</span>
                    </div>
                    <div style="height:.375rem;border-radius:999px;background:var(--ui-line-soft);overflow:hidden;">
                        <div style="height:100%;width:{{ $pct }}%;border-radius:999px;background:var(--ui-accent);"></div>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    @if(isset($byMethod) && $byMethod->count() > 0)
    <div class="ui-panel">
        <div class="ui-panel-head"><span class="ui-panel-title">حسب طريقة الدفع</span></div>
        <div class="ui-panel-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(11rem,1fr));gap:.5rem;">
                @foreach($byMethod as $method => $data)
                <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.5rem .625rem;border:1px solid var(--ui-line);border-radius:.5rem;background:var(--ui-sunken);">
                    <div>
                        <div style="font-size:.75rem;font-weight:600;color:var(--ui-ink);">{{ \App\Models\Expense::paymentMethodLabel($method) }}</div>
                        <div style="font-size:.625rem;color:var(--ui-ink-3);">{{ $data['count'] }} عملية</div>
                    </div>
                    <div class="num t-strong">{{ number_format($data['total'], 0) }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- الفلاتر --}}
    <form method="GET" class="ui-bar">
        <label class="ui-search">
            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.8"/>
                <path d="M13.5 13.5L17 17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            <input type="search" name="search" value="{{ request('search') }}"
                   placeholder="اسم المستلم أو الوصف…" aria-label="بحث في المصروفات">
        </label>

        <select name="category" onchange="this.form.submit()" aria-label="الفئة">
            <option value="">جميع الفئات</option>
            @foreach($categories as $key => $label)
            <option value="{{ $key }}" @selected(request('category') == $key)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="payment_method" onchange="this.form.submit()" aria-label="طريقة الدفع">
            <option value="">كل طرق الدفع</option>
            <option value="cash" @selected(request('payment_method')=='cash')>نقداً من الصندوق</option>
            <option value="bank_transfer" @selected(request('payment_method')=='bank_transfer')>تحويل بنكي</option>
            <option value="later" @selected(request('payment_method')=='later')>لاحقاً</option>
        </select>

        <label class="inline" for="ex-from">من</label>
        <input id="ex-from" type="date" name="date_from" value="{{ request('date_from') }}" onchange="this.form.submit()">
        <label class="inline" for="ex-to">إلى</label>
        <input id="ex-to" type="date" name="date_to" value="{{ request('date_to') }}" onchange="this.form.submit()">

        <select name="shift_id" onchange="this.form.submit()" aria-label="الوردية">
            <option value="">جميع الورديات</option>
            @foreach($availableShifts as $shift)
            <option value="{{ $shift->id }}" @selected(request('shift_id') == $shift->id)>
                {{ $shift->shift_date->format('d/m/Y') }}
                @if(auth()->user()->isAdmin()) — {{ $shift->user->name }} @endif
                ({{ $shift->is_closed ? 'مقفلة' : 'مفتوحة' }})
            </option>
            @endforeach
        </select>

        <button type="submit" class="sr-only">بحث</button>
        @if(request()->hasAny(['category','payment_method','date_from','date_to','search','shift_id']))
        <a href="{{ route('expenses.index') }}" class="ui-btn">مسح الفلاتر</a>
        @endif
    </form>

    {{-- الجدول --}}
    <div class="ui-panel">
        <div class="ui-table-wrap">
            <table class="ui-table" dir="rtl" id="expensesTable">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>الفئة</th>
                        <th class="num">المبلغ (ر.ي)</th>
                        <th>طريقة الدفع</th>
                        <th>اسم المستلم</th>
                        <th>الوصف</th>
                        <th>سُجِّل بواسطة</th>
                        @canany(['expenses.edit','expenses.delete'])
                        <th class="t-actions">إجراءات</th>
                        @endcanany
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                    @php $pm = $expense->payment_method ?? 'cash'; @endphp
                    <tr>
                        <td style="white-space:nowrap;color:var(--ui-ink-2);">{{ $expense->expense_date->format('d/m/Y') }}</td>
                        <td><span class="ui-chip">{{ \App\Models\Expense::categoryLabel($expense->category) }}</span></td>
                        <td class="num num--neg t-strong">{{ number_format($expense->amount, 0) }}</td>
                        <td>
                            <span class="ui-chip {{ $pm === 'cash' ? 'ui-chip--ok' : ($pm === 'bank_transfer' ? 'ui-chip--info' : 'ui-chip--warn') }}">
                                {{ \App\Models\Expense::paymentMethodLabel($pm) }}
                            </span>
                        </td>
                        <td>
                            {{ $expense->recipient_name ?? '—' }}
                            @if($expense->employee_id)
                            <a href="{{ route('employees.withdrawals', $expense->employee_id) }}"
                               class="ui-chip ui-chip--warn" title="مصروف لموظف — يُخصم من راتبه">موظف</a>
                            @endif
                        </td>
                        <td style="color:var(--ui-ink-2);max-width:16rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $expense->description ?? '—' }}
                        </td>
                        <td style="color:var(--ui-ink-2);">{{ $expense->paidBy?->name ?? '—' }}</td>
                        @canany(['expenses.edit','expenses.delete'])
                        <td class="t-actions">
                            @can('expenses.edit')
                            <a href="{{ route('expenses.edit', $expense) }}" class="ui-btn ui-btn--sm">تعديل</a>
                            @endcan
                            @can('expenses.delete')
                            <form method="POST" action="{{ route('expenses.destroy', $expense) }}" style="display:inline;"
                                  onsubmit="return confirm('حذف هذا المصروف؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ui-btn ui-btn--sm ui-btn--danger">حذف</button>
                            </form>
                            @endcan
                        </td>
                        @endcanany
                    </tr>
                    @empty
                    <tr><td colspan="8"><div class="ui-empty">لا توجد مصروفات مسجّلة</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($expenses->hasPages())
        <div class="ui-actions">
            <x-pagination-info :items="$expenses" />
            <div style="margin-inline-start:auto;">{{ $expenses->links() }}</div>
        </div>
        @endif
    </div>

</div>
@endsection
