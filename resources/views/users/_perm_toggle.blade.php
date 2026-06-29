{{-- Reusable permission toggle button --}}
{{-- Variables: $key, $p (permission array), $user, $color (blue|green|amber|red) --}}
@php
$colorMap = [
    'blue'  => ['on' => 'bg-blue-500 border-blue-500',   'ring' => 'ring-blue-200',  'dot' => 'bg-blue-400'],
    'green' => ['on' => 'bg-green-500 border-green-500', 'ring' => 'ring-green-200', 'dot' => 'bg-green-400'],
    'amber' => ['on' => 'bg-amber-500 border-amber-500', 'ring' => 'ring-amber-200', 'dot' => 'bg-amber-400'],
    'red'   => ['on' => 'bg-red-500 border-red-500',     'ring' => 'ring-red-200',   'dot' => 'bg-red-400'],
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
            title="{{ $isOn ? 'إيقاف: ' : 'تفعيل: ' }}{{ $p['label'] }}"
            class="relative w-9 h-9 rounded-xl transition-all duration-200 flex items-center justify-center border-2 disabled:opacity-40 hover:scale-105 active:scale-95
                   {{ $isOn
                       ? ($isCustom
                           ? 'bg-purple-500 border-purple-500 ring-2 ring-offset-1 ring-purple-300'
                           : $c['on'] . ' hover:ring-2 hover:ring-offset-1 ' . $c['ring'])
                       : 'bg-white border-gray-300 hover:border-gray-400 hover:bg-gray-50' }}">

        @if($isOn)
            {{-- Active: solid checkmark --}}
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
        @else
            {{-- Inactive: small hollow circle — clearly an "off" state, not a locked/disabled state --}}
            <span class="w-3 h-3 rounded-full border-2 border-gray-300 bg-transparent"></span>
        @endif

        @if($isCustom)
            <span class="absolute -top-1 -left-1 w-2.5 h-2.5 bg-purple-500 rounded-full border-2 border-white"></span>
        @endif
    </button>
</form>
