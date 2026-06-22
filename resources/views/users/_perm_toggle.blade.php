{{-- Reusable permission toggle button --}}
{{-- Variables: $key, $p (permission array), $user, $color (blue|green|amber|red) --}}
@php
$colorMap = [
    'blue'  => ['on' => 'bg-blue-500',   'off' => 'bg-gray-200', 'ring' => 'ring-blue-200',  'icon_on' => 'text-white', 'border_on' => 'border-blue-500'],
    'green' => ['on' => 'bg-green-500',  'off' => 'bg-gray-200', 'ring' => 'ring-green-200', 'icon_on' => 'text-white', 'border_on' => 'border-green-500'],
    'amber' => ['on' => 'bg-amber-500',  'off' => 'bg-gray-200', 'ring' => 'ring-amber-200', 'icon_on' => 'text-white', 'border_on' => 'border-amber-500'],
    'red'   => ['on' => 'bg-red-500',    'off' => 'bg-gray-200', 'ring' => 'ring-red-200',   'icon_on' => 'text-white', 'border_on' => 'border-red-500'],
];
$c = $colorMap[$color ?? 'blue'];
$isOn = $p['is_granted'];
$isCustom = $p['is_custom'];
@endphp
<form method="POST" action="{{ route('users.togglePermission', $user) }}"
      x-data="{ busy: false }" @submit="busy = true"
      class="inline-flex">
    @csrf
    <input type="hidden" name="permission" value="{{ $key }}">
    <input type="hidden" name="grant" value="{{ $isOn ? '0' : '1' }}">
    <button type="submit" :disabled="busy"
            title="{{ $p['label'] }}"
            class="relative w-9 h-9 rounded-xl transition-all duration-200 flex items-center justify-center border-2 disabled:opacity-50 hover:scale-105 active:scale-95
                   {{ $isOn
                       ? ($isCustom ? 'bg-purple-500 border-purple-500 ring-2 ring-offset-1 ring-purple-300' : $c['on'] . ' border-transparent hover:ring-2 hover:ring-offset-1 ' . $c['ring'])
                       : 'bg-white border-gray-200 hover:border-gray-300 hover:bg-gray-50' }}">
        @if($isOn)
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
        </svg>
        @else
        <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        @endif
        @if($isCustom)
        <span class="absolute -top-1 -left-1 w-2.5 h-2.5 bg-purple-500 rounded-full border-2 border-white"></span>
        @endif
    </button>
</form>
