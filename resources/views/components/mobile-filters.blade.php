@props(['filters' => [], 'submit_text' => 'تطبيق الفلاتر'])

<div x-data="{ open: false }" class="mb-4 lg:hidden">
    <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 bg-white border border-gray-200 rounded-lg hover:border-gray-300 transition">
        <span class="font-medium text-gray-700 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
            </svg>
            الفلاتر
        </span>
        <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open" x-cloak class="mt-2 bg-white border border-gray-200 rounded-lg p-4 space-y-3">
        {{ $slot }}
        <button type="submit" class="w-full px-4 py-2 text-sm font-medium text-white rounded-lg transition" style="background:#0F4C75;">
            {{ $submit_text }}
        </button>
    </div>
</div>

<style>
    @media (min-width: 1024px) {
        [x-show]:not([x-cloak]) {
            display: block !important;
        }
    }
</style>
