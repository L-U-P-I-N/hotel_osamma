{{--
    عقدة واحدة في شجرة الحسابات — تستدعي نفسها لأبنائها.
    $node: مصفوفة من COAService::buildTree()
--}}
@php
    $hasChildren = !empty($node['children']);
    // فئات Tailwind بدل الألوان الثابتة (inline hex) — تُظلَّم تلقائياً في
    // الوضع الليلي عبر app-theme.css العام، بدل ألوان زاهية ثابتة تؤذي العين
    // على خلفية داكنة.
    $typeStyles  = [
        'asset'     => ['cls' => 'bg-blue-100 text-blue-700',       'label' => 'أصول'],
        'liability' => ['cls' => 'bg-red-100 text-red-700',         'label' => 'خصوم'],
        'equity'    => ['cls' => 'bg-violet-100 text-violet-700',   'label' => 'حقوق ملكية'],
        'revenue'   => ['cls' => 'bg-emerald-100 text-emerald-700', 'label' => 'إيرادات'],
        'expense'   => ['cls' => 'bg-orange-100 text-orange-700',   'label' => 'مصروفات'],
    ];
    $style = $typeStyles[$node['type']] ?? ['cls' => 'bg-gray-100 text-gray-600', 'label' => $node['type']];
@endphp

<li x-data="{ open: {{ $node['level'] <= 1 ? 'true' : 'false' }} }" class="coa-node">
    <div class="coa-row" style="--indent: {{ ($node['level'] - 1) * 1.25 }}rem;">

        {{-- زر الطي / التوسيع، أو نقطة للورقة --}}
        @if($hasChildren)
        <button type="button" @click="open = !open"
                class="coa-toggle" :aria-expanded="open.toString()"
                :aria-label="open ? 'طيّ {{ $node['code'] }}' : 'توسيع {{ $node['code'] }}'">
            <svg class="w-3.5 h-3.5 transition-transform" :class="open && '-rotate-90'"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
        @else
        <span class="coa-leaf-dot" aria-hidden="true"></span>
        @endif

        <span class="coa-code">{{ $node['code'] }}</span>

        <span class="coa-name-ar text-gray-800 {{ $node['level'] <= 2 ? 'font-bold' : '' }}">{{ $node['name_ar'] }}</span>
        <span class="coa-name-en text-gray-400" dir="ltr">{{ $node['name_en'] }}</span>

        <span class="coa-badges">
            <span class="coa-chip {{ $style['cls'] }}">
                {{ $style['label'] }}
            </span>

            <span class="coa-chip coa-chip-muted" title="الرصيد الطبيعي">
                {{ $node['normal_balance'] === 'debit' ? 'مدين' : 'دائن' }}
            </span>

            @if($node['is_posting'])
            <span class="coa-chip bg-emerald-100 text-emerald-700" title="يقبل القيود">قابل للترحيل</span>
            @else
            <span class="coa-chip coa-chip-muted" title="حساب تجميعي">تجميعي</span>
            @endif

            @unless($node['is_active'])
            <span class="coa-chip bg-red-100 text-red-700">موقوف</span>
            @endunless
        </span>
    </div>

    @if($hasChildren)
    <ul x-show="open" x-cloak x-collapse class="coa-children">
        @foreach($node['children'] as $child)
            @include('partials.coa-node', ['node' => $child])
        @endforeach
    </ul>
    @endif
</li>
