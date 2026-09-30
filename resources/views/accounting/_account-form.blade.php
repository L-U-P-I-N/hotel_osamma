{{--
    لوحة الحساب — قراءة أولاً ثم تحرير.

    ترتيبها يتبع ترتيب السؤال في ذهن المحاسب: ما هذا الحساب (الكود والاسم)، ثم
    كم رصيده، ثم أين موقعه في الشجرة وما طبيعته، ثم ما الذي يجوز تغييره. الحقول
    المشتقّة (النوع، الطبيعة، الحساب الختامي) تُعرض حقائقَ لا حقولَ إدخال، لأنها
    كلها تتبع الحساب الأب — وإدخالها يدوياً يفتح باب حسابٍ يخالف أصله.

    $editing / $creating / $parents / $lockedCodes — من ChartOfAccountController
--}}
@php
    $isNew   = $creating !== null;
    $account = $editing;
    $parent  = $isNew ? ($creating['parent'] ?? null) : $account?->parent;

    $typeLabels = [
        'asset' => 'أصول', 'liability' => 'خصوم', 'equity' => 'حقوق ملكية',
        'revenue' => 'إيرادات', 'expense' => 'مصروفات',
    ];
    $deptLabels = [
        'rooms' => 'الغرف', 'fnb' => 'الأطعمة والمشروبات', 'spa' => 'المنتجع',
        'laundry' => 'المغسلة', 'parking' => 'المواقف', 'admin' => 'الإدارة',
        'sales' => 'المبيعات والتسويق', 'maintenance' => 'الصيانة', 'utilities' => 'المرافق',
    ];

    // قفلان مختلفان: بنيوي يمنع كل تعديل، وترحيليّ يمنع الإيقاف وحده
    $structureLock = $account?->editBlocker();
    $postingLock   = $account !== null && in_array($account->code, $lockedCodes, true);
    $blocker       = $account?->deletionBlocker();
    $suspendLock   = $structureLock !== null || $postingLock;

    $effectiveType = $account?->type ?? $parent?->type;
    $balance       = $account?->balance_with_children;
@endphp

@if(!$isNew && $account === null)
    {{-- لا حساب مختار: دعوة إلى الفعل بدل نموذج فارغ يوحي بأنه جاهز للحفظ --}}
    <div class="coa-pane-head">
        <span class="coa-pane-title">بيانات الحساب</span>
    </div>
    <div class="coa-empty" style="padding:2.5rem 1.25rem;">
        <svg viewBox="0 0 48 48" fill="none" style="width:2.5rem;height:2.5rem;margin:0 auto .75rem;opacity:.35;">
            <path d="M8 10h14l3 4h15v24H8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
            <path d="M16 24h16M16 30h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <p style="margin-bottom:.25rem;">اختر حساباً من الشجرة لعرض بياناته ورصيده.</p>
        <p style="font-size:.6875rem;">أو أضف حساباً فرعياً بعلامة <strong>+</strong> على أيّ سطر.</p>
    </div>
@else
<form method="POST"
      action="{{ $isNew ? route('coa.store') : route('coa.update', $account->code) }}"
      class="coa-detail">
    @csrf
    @unless($isNew) @method('PUT') @endunless

    {{-- ترويسة: هوية الحساب ورصيده --}}
    <div class="coa-pane-head" style="align-items:flex-start;gap:.625rem;padding:.75rem;">
        <div style="min-width:0;flex:1;">
            <div style="display:flex;align-items:center;gap:.5rem;">
                <span class="coa-code coa-row--t-{{ $effectiveType }}"
                      style="font-size:.9375rem;">{{ $isNew ? ($creating['suggested'] ?? '····') : $account->code }}</span>
                <span style="font-size:.625rem;font-weight:700;color:var(--coa-ink-3);">
                    {{ $typeLabels[$effectiveType] ?? '' }}
                </span>
            </div>
            <p style="font-size:.8125rem;font-weight:700;margin-top:.125rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                {{ $isNew ? 'حساب جديد' : $account->name_ar }}
            </p>
        </div>

        @unless($isNew)
        <div style="text-align:end;flex-shrink:0;">
            <div style="font-size:.5625rem;color:var(--coa-ink-3);">الرصيد شاملاً الفروع</div>
            <div dir="ltr" class="coa-amount {{ $balance < 0 ? 'is-negative' : '' }}"
                 style="font-size:1rem;font-weight:600;min-width:0;">
                {{ $balance < 0 ? '(' . number_format(abs($balance), 2) . ')' : number_format($balance, 2) }}
            </div>
        </div>
        @endunless
    </div>

    <div class="coa-detail-scroll">

        @if($structureLock !== null)
        <p class="coa-note coa-note--warn" style="margin-top:.75rem;">{{ $structureLock }}</p>
        @endif

        @if($errors->any())
        <div class="coa-note coa-note--error" style="margin-top:.75rem;">
            @foreach($errors->all() as $error)
            <div>• {{ $error }}</div>
            @endforeach
        </div>
        @endif

        {{-- الهوية المحاسبية: حقائق تُقرأ، لا حقول تُملأ --}}
        @unless($isNew)
        <dl class="coa-ident">
            <div>
                <dt>الحساب الأب</dt>
                <dd>{{ $parent ? $parent->code . ' — ' . $parent->name_ar : 'حساب رئيسي' }}</dd>
            </div>
            <div>
                <dt>طبيعة الحساب</dt>
                <dd>{{ $account->normal_balance === 'debit' ? 'مدين' : 'دائن' }}</dd>
            </div>
            <div>
                <dt>الحساب الختامي</dt>
                <dd>{{ $account->closing_statement }}</dd>
            </div>
            <div>
                <dt>المستوى</dt>
                <dd>{{ $account->level }} — {{ $account->is_posting ? 'قابل للترحيل' : 'تجميعي' }}</dd>
            </div>
            <div style="grid-column:1/-1;">
                <dt>المصدر</dt>
                <dd>
                    {{ $account->is_system ? 'شجرة USALI الأساسية' : 'مُضاف يدوياً' }}
                    {{ $structureLock !== null ? '· محميّ' : '' }}
                </dd>
            </div>
        </dl>
        @endunless

        {{-- ما يُدخله المستخدم --}}
        <div class="coa-section">{{ $isNew ? 'الحساب الجديد' : 'التحرير' }}</div>

        @if($isNew)
        <div class="coa-field">
            <label for="coa-parent">الحساب الأب <span class="req">*</span></label>
            @if($creating['parent_locked'] ?? false)
                <input type="hidden" name="parent_code" value="{{ $parent->code }}">
                <input id="coa-parent" type="text" class="coa-input" readonly
                       value="{{ $parent->code }} — {{ $parent->name_ar }}">
            @else
                <select id="coa-parent" name="parent_code" required class="coa-input">
                    <option value="">— اختر الحساب الأب —</option>
                    @foreach($parents as $candidate)
                    <option value="{{ $candidate->code }}" @selected(old('parent_code') === $candidate->code)>
                        {{ str_repeat('· ', max(0, $candidate->level - 1)) }}{{ $candidate->code }} — {{ $candidate->name_ar }}
                    </option>
                    @endforeach
                </select>
                <p class="coa-hint">لا تظهر هنا الحسابات التي رُحِّلت عليها قيود ولا حسابات المستوى الرابع.</p>
            @endif
        </div>

        <div class="coa-field">
            <label for="coa-code">رقم الحساب <span class="req">*</span></label>
            <input id="coa-code" type="text" name="code" inputmode="numeric" maxlength="4" required
                   class="coa-input is-mono" value="{{ old('code', $creating['suggested']) }}" placeholder="····">
            <p class="coa-hint">مقترح من موضعه تحت الأب — يمكن تغييره.</p>
        </div>
        @endif

        <div class="coa-field">
            <label for="coa-name">اسم الحساب <span class="req">*</span></label>
            <input id="coa-name" type="text" name="name_ar" required maxlength="150" class="coa-input"
                   value="{{ old('name_ar', $account?->name_ar) }}" @readonly($structureLock !== null)>
        </div>

        <div class="coa-field">
            <label for="coa-name-en">الاسم بالإنجليزية</label>
            <input id="coa-name-en" type="text" name="name_en" maxlength="150" dir="ltr" class="coa-input"
                   value="{{ old('name_en', $account?->name_en) }}"
                   placeholder="يُنسخ من العربي إن تُرك فارغاً" @readonly($structureLock !== null)>
        </div>

        <div class="coa-field">
            <label for="coa-dept">القسم (مركز التكلفة)</label>
            <select id="coa-dept" name="department" class="coa-input" @disabled($structureLock !== null)>
                <option value="">بلا قسم</option>
                @foreach($deptLabels as $value => $label)
                <option value="{{ $value }}" @selected(old('department', $account?->department ?? $parent?->department) === $value)>
                    {{ $label }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="coa-field">
            <label for="coa-notes">ملاحظات</label>
            <input id="coa-notes" type="text" name="notes" maxlength="500" class="coa-input"
                   value="{{ old('notes', $account?->notes) }}" @readonly($structureLock !== null)>
        </div>

        @unless($isNew)
        <div class="coa-section">الحالة</div>
        <div class="coa-field">
            <label class="coa-toggle" style="width:100%;height:2.25rem;
                   {{ $suspendLock ? 'opacity:.55;cursor:not-allowed;' : 'cursor:pointer;' }}">
                <input type="checkbox" name="suspended" value="1"
                       @checked(old('suspended', !$account->is_active)) @disabled($suspendLock)>
                إيقاف الحساب
            </label>

            @if($postingLock && $structureLock === null)
            <p class="coa-note coa-note--warn" style="margin:.5rem 0 0;">
                هذا الحساب مستعمل في ترحيل العمليات اليومية، فإيقافه يُعطّل تسجيلها — ولذلك لا يقبل الإيقاف.
            </p>
            @endif
        </div>
        @endunless
    </div>

    {{-- شريط الأوامر: ثابت أسفل اللوحة فلا يحتاج المستخدم للنزول إليه --}}
    <div class="coa-actions">
        @if($structureLock === null)
        <button type="submit" class="coa-btn coa-btn--primary">حفظ</button>
        @else
        <span class="coa-btn coa-btn--primary is-off" title="{{ $structureLock }}">حفظ</span>
        @endif

        @unless($isNew)
        <a href="{{ route('coa.index', array_merge(request()->query(), ['new' => 1, 'parent' => $account->code, 'edit' => null])) }}#coa-detail"
           class="coa-btn" data-coa-open>+ فرعي</a>
        @endunless

        @if(!$isNew && $blocker === null)
        <button type="submit" form="coa-delete" class="coa-btn coa-btn--danger"
                onclick="return confirm('حذف الحساب {{ $account->code }} — {{ $account->name_ar }} نهائياً؟')">حذف</button>
        @elseif(!$isNew)
        <span class="coa-btn coa-btn--danger is-off" title="{{ $blocker }}">حذف</span>
        @endif

        <a href="{{ route('coa.index', request()->except(['edit','new','parent'])) }}"
           class="coa-btn" style="margin-inline-start:auto;">إغلاق</a>
    </div>
</form>

@if(!$isNew && $blocker === null)
<form id="coa-delete" method="POST" action="{{ route('coa.destroy', $account->code) }}" hidden>
    @csrf @method('DELETE')
</form>
@endif
@endif
