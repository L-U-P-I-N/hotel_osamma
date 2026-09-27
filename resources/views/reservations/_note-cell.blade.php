{{--
    خانة الملاحظات في جدول الحجوزات.

    الأيقونة وحدها كانت تُخفي نصّ الملاحظة حتى يفتح الموظف نافذةً لكل صفّ، فلا
    يُقرأ شيء أثناء المرور السريع على القائمة. هنا يظهر نصّ أشدّ ملاحظة قائمة
    بجوار الأيقونة بخطٍّ صغير، ولمن أراد قائمةً نظيفة زرُّ طيّ يُخفي النصوص
    ويُبقي الأيقونة فقط — واختياره محفوظ في المتصفح فيبقى بين الصفحات.

    المتغيرات: $res (الحجز)
--}}
@php
    $openNotes = $res->openQuickNotes;
    $topNote   = $openNotes->first(fn($n) => $n->color === 'red')
        ?? $openNotes->first(fn($n) => $n->color === 'amber')
        ?? $openNotes->first();
    $noteColor = $topNote?->color ?? 'none';
    $noteClasses = [
        'red'   => 'bg-red-100 text-red-600 hover:bg-red-200',
        'amber' => 'bg-amber-100 text-amber-600 hover:bg-amber-200',
        'blue'  => 'bg-blue-100 text-blue-600 hover:bg-blue-200',
        'none'  => 'text-gray-300 hover:bg-gray-100 hover:text-gray-500',
    ];
    $textClasses = [
        'red'   => 'bg-red-50 border-red-200 text-red-800',
        'amber' => 'bg-amber-50 border-amber-200 text-amber-800',
        'blue'  => 'bg-blue-50 border-blue-200 text-blue-800',
        'none'  => 'bg-gray-50 border-gray-200 text-gray-600',
    ];
    $tooltip = $openNotes->isNotEmpty()
        ? $topNote->type_label . ': ' . \Illuminate\Support\Str::limit($topNote->body, 90)
        : 'لا توجد ملاحظات — اضغط لإضافة واحدة';
@endphp
<td class="px-2 py-3 align-top" data-label="ملاحظات">
    <div class="flex items-start gap-1.5" data-note-cell="{{ $res->id }}">
        {{-- الأيقونة: لونها يتبع أشدّ ملاحظة قائمة، وعدّادها عدد القائم منها --}}
        <button type="button"
                data-notes-btn="{{ $res->id }}"
                data-notes-count="{{ $openNotes->count() }}"
                onclick="event.stopPropagation(); openNotes({{ $res->id }}, this)"
                title="{{ $tooltip }}"
                class="relative w-8 h-8 flex-shrink-0 rounded-lg transition inline-flex items-center justify-center {{ $noteClasses[$noteColor] }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            @if($openNotes->isNotEmpty())
            <span data-notes-badge class="absolute -top-1 -left-1 min-w-[16px] h-4 px-1 rounded-full bg-red-600 text-white text-[10px] font-bold leading-4">{{ $openNotes->count() }}</span>
            @endif
        </button>

        @if($openNotes->isNotEmpty())
        {{-- نصّ الملاحظة بجوار الأيقونة: يُقرأ بلا فتح نافذة، ويُطوى بالزر --}}
        <div data-note-text="{{ $res->id }}" class="min-w-0 max-w-[15rem]">
            <button type="button"
                    onclick="event.stopPropagation(); openNotes({{ $res->id }}, this.closest('[data-note-cell]').querySelector('[data-notes-btn]'))"
                    title="{{ $tooltip }}"
                    class="block w-full text-right rounded-lg border px-2 py-1 text-[11px] leading-snug transition hover:brightness-95 {{ $textClasses[$noteColor] }}">
                <span class="font-bold block text-[10px] opacity-80">{{ $topNote->type_label }}</span>
                <span class="block line-clamp-2">{{ \Illuminate\Support\Str::limit($topNote->body, 70) }}</span>
                @if($openNotes->count() > 1)
                <span class="block text-[10px] font-semibold opacity-70 mt-0.5">+{{ $openNotes->count() - 1 }} ملاحظة أخرى</span>
                @endif
            </button>
        </div>
        @endif
    </div>
</td>
