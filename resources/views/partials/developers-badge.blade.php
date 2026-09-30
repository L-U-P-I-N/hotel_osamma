{{--
    شعار فريق التطوير وأسماء المطوّرين للتواصل — في شريط الأعلى نفسه، فيبقى
    ظاهراً في كل الصفحات بلا استثناء.

    على الشاشات العريضة تظهر الأسماء والأرقام مباشرةً؛ وعلى الجوال يبقى الشعار
    وحده زرّاً يفتح قائمة صغيرة بها الأسماء وروابط اتصال — فلا يزاحم عنوان
    الصفحة ولا يُقتطع.
--}}
@php
    $devTeam    = config('developers.team_name', 'فريق التطوير');
    $devMembers = config('developers.members', []);
@endphp

@if(!empty($devMembers))
<div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative flex-shrink-0">

    {{-- الشعار: حرفا الفريق داخل درع — لا يعتمد على ملف صورة قد يُفقد --}}
    <button type="button" @click="open = !open" @click.outside="open = false"
            :aria-expanded="open.toString()"
            title="{{ $devTeam }} — أسماء المطوّرين للتواصل"
            class="flex items-center gap-2 h-8 pl-1.5 pr-1.5 rounded-lg border border-transparent hover:border-gray-200 hover:bg-gray-50 transition-colors">
        <span class="w-6 h-6 rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm"
              style="background:linear-gradient(135deg,#0F4C75,#D4A574);">
            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
            </svg>
        </span>

        {{-- الأسماء على الشاشات الواسعة فقط — مصفوفة عمودياً لتبقى مرتبة --}}
        <span class="hidden xl:flex flex-col items-start leading-tight text-right">
            <span class="text-[10px] font-bold text-gray-500">{{ $devTeam }}</span>
            <span class="text-[10px] text-gray-400 whitespace-nowrap">
                {{ collect($devMembers)->pluck('name')->implode(' · ') }}
            </span>
        </span>

        <svg class="w-3 h-3 text-gray-400 flex-shrink-0 transition-transform" :class="open && 'rotate-180'"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- القائمة: أسماء المطوّرين وأرقامهم، والرقم رابط اتصال مباشر من الجوال --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-end="opacity-0"
         class="absolute left-0 mt-2 w-64 max-w-[calc(100vw-1.5rem)] bg-white rounded-xl shadow-2xl border border-gray-200 z-[80] overflow-hidden">
        <div class="px-3 py-2.5 flex items-center gap-2" style="background:linear-gradient(135deg,#0F4C75,#0a3555);">
            <span class="w-6 h-6 rounded-lg flex items-center justify-center flex-shrink-0"
                  style="background:rgba(255,255,255,0.15);">
                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                </svg>
            </span>
            <div class="min-w-0">
                <div class="text-xs font-bold text-white truncate">{{ $devTeam }}</div>
                <div class="text-[10px]" style="color:#a8c8e0;">مطوّرو النظام — للتواصل</div>
            </div>
        </div>
        <ul class="ui-divide">
            @foreach($devMembers as $member)
            <li class="px-3 py-2.5 flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-gray-700 min-w-0 truncate">{{ $member['name'] }}</span>
                @if(!empty($member['phone']))
                <a href="tel:{{ $member['phone'] }}" dir="ltr"
                   class="text-[11px] font-mono font-bold px-2 py-1 rounded-lg flex-shrink-0 transition hover:brightness-95"
                   style="background:var(--ui-accent-bg);color:var(--ui-accent);">{{ $member['phone'] }}</a>
                @endif
            </li>
            @endforeach
        </ul>
    </div>
</div>
@endif
