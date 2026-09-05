{{--
    عقدة واحدة في شجرة الحسابات — تستدعي نفسها لأبنائها.
    $node: مصفوفة من COAService::buildTree()
--}}
@php
    $hasChildren = !empty($node['children']);
    $typeStyles  = [
        'asset'     => ['bg' => '#eff6ff', 'fg' => '#1d4ed8', 'label' => 'أصول'],
        'liability' => ['bg' => '#fef2f2', 'fg' => '#b91c1c', 'label' => 'خصوم'],
        'equity'    => ['bg' => '#f5f3ff', 'fg' => '#6d28d9', 'label' => 'حقوق ملكية'],
        'revenue'   => ['bg' => '#ecfdf5', 'fg' => '#047857', 'label' => 'إيرادات'],
        'expense'   => ['bg' => '#fff7ed', 'fg' => '#c2410c', 'label' => 'مصروفات'],
    ];
    $style = $typeStyles[$node['type']] ?? ['bg' => '#f3f4f6', 'fg' => '#374151', 'label' => $node['type']];
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

        <span class="coa-name-ar {{ $node['level'] <= 2 ? 'font-bold' : '' }}">{{ $node['name_ar'] }}</span>
        <span class="coa-name-en" dir="ltr">{{ $node['name_en'] }}</span>

        <span class="coa-badges">
            <span class="coa-chip" style="background:{{ $style['bg'] }};color:{{ $style['fg'] }};">
                {{ $style['label'] }}
            </span>

            <span class="coa-chip coa-chip-muted" title="الرصيد الطبيعي">
                {{ $node['normal_balance'] === 'debit' ? 'مدين' : 'دائن' }}
            </span>

            @if($node['is_posting'])
            <span class="coa-chip" style="background:#ecfdf5;color:#047857;" title="يقبل القيود">قابل للترحيل</span>
            @else
            <span class="coa-chip coa-chip-muted" title="حساب تجميعي">تجميعي</span>
            @endif

            @unless($node['is_active'])
            <span class="coa-chip" style="background:#fef2f2;color:#b91c1c;">موقوف</span>
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
