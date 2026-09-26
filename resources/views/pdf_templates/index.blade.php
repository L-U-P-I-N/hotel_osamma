@extends('layouts.app')
@section('title', 'قوالب التصدير')
@section('page-title', 'قوالب التصدير')

@section('content')
<div class="space-y-5">

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center justify-between flex-wrap gap-3">
        <div>
            <h2 class="text-xl font-black text-gray-800">قوالب تصدير PDF</h2>
            <p class="text-sm text-gray-500 mt-1">صمّم شكل مستنداتك بنفسك، واختر القالب عند كل تصدير.</p>
        </div>
        <a href="{{ route('pdf-templates.create') }}"
           class="px-4 py-2.5 rounded-xl text-white text-sm font-bold" style="background:#0F4C75">
            + قالب جديد
        </a>
    </div>

    @if(session('success'))
    <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    {{-- القوالب الثابتة تُذكر صراحةً كي لا يبحث المدير عن سبب غيابها --}}
    <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4">
        <p class="text-sm font-bold text-amber-900 mb-1.5">تقارير بقوالب ثابتة لا تقبل التخصيص</p>
        <p class="text-xs text-amber-800 mb-2">تصميمها مُلزِم لأن جهات خارجية تعتمد شكلها:</p>
        <div class="flex flex-wrap gap-2">
            @foreach($lockedKeys as $report)
            <span class="px-2.5 py-1 rounded-lg bg-white border border-amber-200 text-xs font-semibold text-amber-900">
                {{ $report['label'] }}
            </span>
            @endforeach
        </div>
    </div>

    @forelse($templates as $reportKey => $group)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
            <h3 class="font-bold text-gray-800 text-sm">{{ \App\Support\ReportRegistry::label($reportKey) }}</h3>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($group as $template)
            <div class="px-5 py-3 flex items-center justify-between gap-3 flex-wrap">
                <div class="min-w-0">
                    <p class="font-semibold text-gray-800 text-sm flex items-center gap-2">
                        {{ $template->name }}
                        @if($template->is_default)
                        <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded">الافتراضي</span>
                        @endif
                        @unless($template->is_active)
                        <span class="px-1.5 py-0.5 bg-gray-100 text-gray-600 text-[10px] font-bold rounded">معطّل</span>
                        @endunless
                    </p>
                    <p class="text-xs text-gray-400">
                        {{ $template->doc_title ?: 'بلا عنوان مخصّص' }} — أنشأه {{ $template->creator?->name ?? '—' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('pdf-templates.edit', $template) }}"
                       class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-600 text-xs hover:bg-gray-50">تعديل</a>
                    <form method="POST" action="{{ route('pdf-templates.destroy', $template) }}"
                          onsubmit="return confirm('حذف القالب «{{ $template->name }}»؟')">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-3 py-1.5 rounded-lg border border-red-200 text-red-600 text-xs hover:bg-red-50">حذف</button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @empty
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-10 text-center">
        <p class="text-gray-500 text-sm mb-4">لا توجد قوالب بعد — التصدير يستعمل التصميم الافتراضي للنظام.</p>
        <a href="{{ route('pdf-templates.create') }}"
           class="inline-block px-5 py-2.5 rounded-xl text-white text-sm font-bold" style="background:#0F4C75">
            صمّم أول قالب
        </a>
    </div>
    @endforelse
</div>
@endsection
