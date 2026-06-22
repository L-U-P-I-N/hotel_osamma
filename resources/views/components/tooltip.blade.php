@props(['text'])

<div x-data x-tooltip.raw="{{ $text }}" class="relative inline-flex items-center gap-1">
    {{ $slot }}
    <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 pointer-events-none whitespace-nowrap z-50 group-hover:opacity-100 transition-opacity">
        {{ $text }}
    </div>
</div>
