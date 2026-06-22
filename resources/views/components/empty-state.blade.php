@props(['icon' => '📭', 'title' => 'لا توجد بيانات', 'message' => '', 'action_text' => null, 'action_url' => null, 'icon_class' => 'text-gray-200'])

<div class="py-12 text-center">
    <div class="mb-4">
        <span class="text-6xl">{{ $icon }}</span>
    </div>
    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $title }}</h3>
    @if($message)
    <p class="text-gray-500 text-sm mb-6">{{ $message }}</p>
    @endif
    @if($action_text && $action_url)
    <a href="{{ $action_url }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-lg transition" style="background:#0F4C75; hover:opacity-90;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        {{ $action_text }}
    </a>
    @else
        {{ $slot }}
    @endif
</div>
