{{--
    حذف وردية — باختيار صريح بين وضعين.

    كان الحذف وضعاً واحداً: تُحذف الوردية وتبقى مستلماتها ومصروفاتها بلا ربط،
    ولا سبيل بعدها لإزالتها من الشاشة. فوردية أُدخلت بالكامل خطأً كانت تُخلّف
    سجلات يتيمة إلى الأبد. وصار الاختيار معروضاً قبل الحذف لا بعده، ومعه عدد
    ما سيُحذف فعلاً كي لا يُضغط الزر على المجهول.

    $shift   : الوردية
    $variant : 'lg' لرأس الوردية المفتوحة، 'sm' لسطر في جدول الورديات السابقة
--}}
@php
    $variant = $variant ?? 'lg';
    $uid     = 'del' . $shift->id;

    // العدّ من withCount إن كان محمَّلاً، وإلا من العلاقة مباشرةً
    $nPayments    = $shift->payments_count    ?? $shift->payments()->count();
    $nWithdrawals = $shift->withdrawals_count ?? $shift->withdrawals()->count();
    $nRefunds     = $shift->refunds_count     ?? $shift->refunds()->count();
    $nExpenses    = \App\Models\Expense::where('shift_id', $shift->id)->count();
    $nAll         = $nPayments + $nWithdrawals + $nRefunds + $nExpenses;

    $isAdmin = auth()->user()->isAdmin();
@endphp

<div x-data="{ open: false, mode: 'detach' }" style="display:inline-block;">

    @if($variant === 'lg')
    <button type="button" @click="open = true" class="ui-btn ui-btn--danger">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
        </svg>
        حذف الوردية
    </button>
    @else
    <button type="button" @click="open = true" class="ui-btn ui-btn--sm ui-btn--danger">حذف</button>
    @endif

    {{--
        النافذة تُنقل إلى body: الزرّ يقع داخل ترويسة الوردية وداخل خلية جدول،
        وللمحيط هناك تحويلاتٌ تجعل position:fixed نسبيّاً إليه لا إلى الشاشة —
        فكانت النافذة تظهر في زاوية بدل وسط الشاشة.
    --}}
    <template x-teleport="body">
    <div x-show="open" x-cloak class="ui-modal"
         @keydown.escape.window="open = false" @click.self="open = false">
        <div class="ui-panel" dir="rtl">

            <div class="ui-panel-head">
                <span class="ui-panel-title">حذف وردية {{ $shift->shift_date?->format('d/m/Y') }}</span>
                <button type="button" class="ui-btn ui-btn--sm" style="margin-inline-start:auto;" @click="open = false">إغلاق</button>
            </div>

            <form method="POST" action="{{ route('shifts.destroy', $shift) }}">
                @csrf @method('DELETE')

                <div class="ui-panel-body" style="display:grid;gap:.625rem;">

                    <label style="display:block;cursor:pointer;border:1px solid var(--ui-line);border-radius:.625rem;padding:.75rem;"
                           :style="mode === 'detach' ? 'border-color:var(--ui-accent);background:var(--ui-accent-bg);' : ''">
                        <span style="display:flex;align-items:flex-start;gap:.5rem;">
                            <input type="radio" name="mode" value="detach" x-model="mode" style="margin-top:.25rem;accent-color:var(--ui-accent);">
                            <span>
                                <span style="display:block;font-size:.8125rem;font-weight:700;color:var(--ui-ink);">
                                    الوردية وحدها
                                </span>
                                <span style="display:block;font-size:.6875rem;color:var(--ui-ink-2);margin-top:.125rem;line-height:1.6;">
                                    تبقى المستلمات والسحبيات والمصروفات محفوظة بلا ربط، وتضمّها لاحقاً
                                    إلى وردية أخرى من نفس الشاشة. اختَرها إذا كانت الوردية نفسها خطأً
                                    (تاريخ غلط أو تقسيم خاطئ) والمال استُلم فعلاً.
                                </span>
                            </span>
                        </span>
                    </label>

                    @if($isAdmin)
                    <label style="display:block;cursor:pointer;border:1px solid var(--ui-line);border-radius:.625rem;padding:.75rem;"
                           :style="mode === 'with_records' ? 'border-color:var(--ui-neg);background:var(--ui-err-bg);' : ''">
                        <span style="display:flex;align-items:flex-start;gap:.5rem;">
                            <input type="radio" name="mode" value="with_records" x-model="mode" style="margin-top:.25rem;accent-color:var(--ui-neg);">
                            <span>
                                <span style="display:block;font-size:.8125rem;font-weight:700;color:var(--ui-neg);">
                                    الوردية مع مستلماتها ومصروفاتها
                                </span>
                                <span style="display:block;font-size:.6875rem;color:var(--ui-ink-2);margin-top:.125rem;line-height:1.6;">
                                    @if($nAll > 0)
                                    سيُحذف معها:
                                    <b style="color:var(--ui-ink);">{{ $nPayments }}</b> مستلمة،
                                    <b style="color:var(--ui-ink);">{{ $nRefunds }}</b> استرجاع،
                                    <b style="color:var(--ui-ink);">{{ $nWithdrawals }}</b> سحب،
                                    <b style="color:var(--ui-ink);">{{ $nExpenses }}</b> مصروف.
                                    ويُعاد احتساب المدفوع في حجوزاتها.
                                    @else
                                    لا سجلات مالية على هذه الوردية — الوضعان سواء.
                                    @endif
                                </span>
                            </span>
                        </span>
                    </label>
                    @endif
                </div>

                <div class="ui-note ui-note--warn" style="margin:0 .875rem .75rem;">
                    <span x-show="mode === 'detach'">
                        لا يمكن التراجع عن حذف الوردية. سجلاتها تبقى ويمكن ضمّها.
                    </span>
                    <span x-show="mode === 'with_records'" x-cloak>
                        لا رجعة في هذا: المستلمات والمصروفات تُحذف معها.
                        فترات تسعير الحجوزات لا تُحذف — هي أساس فواتير النزلاء.
                    </span>
                </div>

                <div class="ui-actions">
                    <button type="submit" class="ui-btn ui-btn--danger"
                            x-text="mode === 'with_records' ? 'حذف الوردية وسجلاتها' : 'حذف الوردية وحدها'"></button>
                    <button type="button" class="ui-btn" @click="open = false">إلغاء</button>
                </div>
            </form>
        </div>
    </div>
    </template>
</div>
