@props(['column', 'label', 'sort_by' => null, 'sort_direction' => 'asc'])

@php
    $current_sort = request('sort_by');
    $current_direction = request('sort_direction', 'asc');
    $new_direction = ($current_sort === $column && $current_direction === 'asc') ? 'desc' : 'asc';

    $sort_url = request()->fullUrlWithQuery([
        'sort_by' => $column,
        'sort_direction' => $new_direction
    ]);

    $is_active = $current_sort === $column;
@endphp

<a href="{{ $sort_url }}" class="inline-flex items-center gap-1.5 hover:text-primary-600 transition group">
    <span>{{ $label }}</span>
    @if($is_active)
        @if($current_direction === 'desc')
        <svg class="w-3.5 h-3.5 text-primary-600" fill="currentColor" viewBox="0 0 24 24">
            <path d="M7 10l5 5 5-5z"/>
        </svg>
        @else
        <svg class="w-3.5 h-3.5 text-primary-600" fill="currentColor" viewBox="0 0 24 24">
            <path d="M7 14l5-5 5 5z"/>
        </svg>
        @endif
    @else
    <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-gray-400" fill="currentColor" viewBox="0 0 24 24">
        <path d="M7 10l5 5 5-5z M7 14l5-5 5 5z" opacity="0.3"/>
    </svg>
    @endif
</a>
