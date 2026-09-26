{{--
    زر تصدير يعرض اختيار القالب حين يكون للتقرير أكثر من قالب.
    الاستعمال: @include('pdf_templates._export_choice', ['reportKey' => 'debts', 'url' => route(...)])
    بقالب واحد أو بلا قوالب يبقى زراً عادياً، فلا نُثقل الموظف باختيار بلا معنى.
--}}
@php
    $choices = \App\Models\PdfTemplate::choicesFor($reportKey ?? '');
    $label   = $label ?? 'تصدير PDF';
@endphp

@if($choices->count() > 1)
<div class="relative inline-block" x-data="{ open: false }">
    <button type="button" @click="open = !open"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-white text-sm font-semibold" style="background:#0F4C75">
        {{ $label }}
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-cloak @click.outside="open = false"
         class="absolute left-0 mt-1 w-60 bg-white rounded-xl shadow-lg border border-gray-200 py-1 z-40">
        <p class="px-3 py-1.5 text-[11px] font-bold text-gray-400">اختر قالب التصدير</p>
        @foreach($choices as $choice)
        <a href="{{ $url }}{{ str_contains($url, '?') ? '&' : '?' }}template={{ $choice->id }}" target="_blank"
           class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
            {{ $choice->name }}
            @if($choice->is_default)<span class="text-[10px] text-emerald-600 font-bold mr-1">(الافتراضي)</span>@endif
        </a>
        @endforeach
        <a href="{{ $url }}{{ str_contains($url, '?') ? '&' : '?' }}template=0" target="_blank"
           class="block px-3 py-2 text-sm text-gray-500 hover:bg-gray-50 border-t border-gray-100">
            تصميم النظام الأصلي
        </a>
    </div>
</div>
@else
<a href="{{ $url }}" target="_blank"
   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-white text-sm font-semibold" style="background:#0F4C75">
    {{ $label }}
</a>
@endif
