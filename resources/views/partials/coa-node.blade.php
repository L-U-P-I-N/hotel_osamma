{{--
    سطر واحد في دليل الحسابات — يستدعي نفسه لفروعه.

    السطر يقرأ كسطر دفتر: الكود، ثم الاسم، ثم الرصيد محاذىً لليسار. لا شارات
    في حالة السكون: نوع الحساب يحمله لونُ كوده، ومستواه تحمله إزاحته ووزن خطه،
    وكونه تجميعياً يحمله وزن رصيده الأخف (رصيدُه جمعُ فروعه لا واقعة مقيَّدة).
    ما عدا ذلك يظهر عند التحويم أو الاختيار وحدهما — فشاشةٌ فيها ٢٢٤ سطراً
    تفقد قابلية المسح إن حمل كل سطر منها خمس شارات.

    $node        : عقدة من COAService::buildTree(..., withBalances: true)
    $canManage   : هل يملك المستخدم تحرير الدليل
    $selectedCode: كود الحساب المفتوح في لوحة التفاصيل
--}}
@php
    $hasChildren  = !empty($node['children']);
    $canManage    = $canManage    ?? false;
    $selectedCode = $selectedCode ?? null;

    $isSelected = $selectedCode !== null && $selectedCode === $node['code'];

    // الترقيم هرمي بخاناته، فالمختار يقع تحت كل عقدة تُطابق خاناتها الأولى —
    // بهذا يُفتح المسار من الجذر إليه بدل أن يختفي داخل فرع مطويّ.
    $onSelectedPath = $selectedCode !== null
        && str_starts_with($selectedCode, substr($node['code'], 0, $node['level']));

    $balance   = $node['balance'] ?? null;
    $isRollup  = !$node['is_posting'];
    $searchKey = $node['code'] . ' ' . $node['name_ar'] . ' ' . $node['name_en'];
@endphp

<li class="coa-node"
    data-code="{{ $node['code'] }}"
    data-search="{{ mb_strtolower($searchKey) }}"
    x-data="{ open: {{ $node['level'] <= 1 || $onSelectedPath ? 'true' : 'false' }} }">

    <div class="coa-row coa-row--t-{{ $node['type'] }}
                @if($isSelected) is-selected @endif
                @unless($node['is_active']) is-suspended @endunless"
         style="--depth: {{ $node['level'] - 1 }}">

        {{-- الطي --}}
        @if($hasChildren)
        <button type="button" class="coa-twisty" @click.stop="open = !open"
                :aria-expanded="open.toString()"
                aria-label="طيّ أو توسيع {{ $node['code'] }}">
            <svg viewBox="0 0 16 16" aria-hidden="true" :class="open && 'is-open'">
                <path d="M10 4L6 8l4 4" fill="none" stroke="currentColor" stroke-width="1.75"
                      stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        @else
        <span class="coa-twisty coa-twisty--leaf" aria-hidden="true"></span>
        @endif

        {{-- الكود: لونه هو نوع الحساب --}}
        <span class="coa-code">{{ $node['code'] }}</span>

        @if($canManage && ($node['is_locked'] ?? false))
        <svg class="coa-lock" viewBox="0 0 12 12" aria-hidden="true">
            <title>حساب أساسي في بنية الشجرة — لا يُعدَّل ولا يُحذف</title>
            <path d="M3 5V3.5a3 3 0 016 0V5M2.5 5h7v5.5h-7z" fill="none"
                  stroke="currentColor" stroke-width="1.1" stroke-linejoin="round"/>
        </svg>
        @endif

        {{-- الاسم --}}
        @if($canManage)
        <a href="{{ route('coa.index', array_merge(request()->query(), ['edit' => $node['code'], 'new' => null, 'parent' => null])) }}#coa-detail"
           class="coa-name" data-coa-open>{{ $node['name_ar'] }}</a>
        @else
        <span class="coa-name">{{ $node['name_ar'] }}</span>
        @endif

        <span class="coa-name-en" dir="ltr">{{ $node['name_en'] }}</span>

        {{-- إضافة فرع — تظهر عند التحويم وحده --}}
        @if($canManage && $node['level'] < 4)
        <a href="{{ route('coa.index', array_merge(request()->query(), ['new' => 1, 'parent' => $node['code'], 'edit' => null])) }}#coa-detail"
           class="coa-add" data-coa-open title="إضافة حساب فرعي تحت {{ $node['code'] }}"
           aria-label="إضافة حساب فرعي تحت {{ $node['code'] }}">+</a>
        @endif

        @unless($node['is_active'])
        <span class="coa-flag">موقوف</span>
        @endunless

        {{-- الرصيد: الرقم هو ما يُمسح بالعين، فيقف وحده في عموده --}}
        @if($balance !== null)
        <span class="coa-amount {{ $isRollup ? 'is-rollup' : '' }} {{ $balance < 0 ? 'is-negative' : '' }}" dir="ltr">
            @if(abs($balance) < 0.005)
                <span class="coa-zero">—</span>
            @else
                {{ $balance < 0 ? '(' . number_format(abs($balance), 0) . ')' : number_format($balance, 0) }}
            @endif
        </span>
        @endif
    </div>

    @if($hasChildren)
    <ul class="coa-children" x-show="open" x-cloak x-collapse
        style="--guide: {{ ($node['level'] - 1) * 1.375 }}rem">
        @foreach($node['children'] as $child)
            @include('partials.coa-node', [
                'node'         => $child,
                'canManage'    => $canManage,
                'selectedCode' => $selectedCode,
            ])
        @endforeach
    </ul>
    @endif
</li>
