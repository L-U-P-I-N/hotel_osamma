{{-- صندوق بحث فوري بنفس سلوك صفحة الحجوزات: النتائج تظهر أثناء الكتابة دون
     ضغط أي زر — يُرسَل النموذج بعد توقّف الكتابة نصف ثانية.
     المتغيرات: $action (وجهة النموذج)، $placeholder، و$hidden (حقول تُمرَّر
     معه كي لا تضيع الفلاتر القائمة كالفترة الزمنية). --}}
@php
    $hidden = $hidden ?? [];
@endphp
<form method="GET" action="{{ $action }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    @foreach($hidden as $name => $value)
    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    <div class="flex gap-2 items-center">
        <div class="relative flex-1">
            <input type="text" name="q" value="{{ $q }}" autocomplete="off"
                   x-data x-init="$el.focus(); $el.setSelectionRange($el.value.length, $el.value.length)"
                   x-on:input.debounce.500ms="$el.form.requestSubmit()"
                   placeholder="{{ $placeholder ?? 'اكتب الاسم...' }}"
                   class="w-full border border-gray-200 rounded-lg px-4 py-2.5 pr-10 text-sm outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 bg-white">
            <svg class="w-4 h-4 text-gray-400 absolute top-1/2 right-3 -translate-y-1/2 pointer-events-none"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        {{-- زر مخفي: يبقى Enter عاملاً لمن يفضّله، والبحث الفوري يغني عنه --}}
        <button type="submit" class="sr-only">بحث</button>
        @if($q !== '')
        <a href="{{ $action }}{{ $hidden ? '?' . http_build_query($hidden) : '' }}"
           class="px-4 py-2.5 text-xs text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition whitespace-nowrap">
            مسح البحث
        </a>
        @endif
    </div>
</form>
