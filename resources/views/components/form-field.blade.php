@props(['name', 'label', 'type' => 'text', 'placeholder' => '', 'value' => '', 'required' => false, 'help' => '', 'errors' => null])

@php
    $has_error = $errors && $errors->has($name);
    $error_message = $has_error ? $errors->first($name) : null;
    $field_value = old($name, $value);
@endphp

<div class="flex flex-col gap-1">
    <label class="text-sm font-medium text-gray-700 flex items-center gap-1">
        {{ $label }}
        @if($required)
        <span class="text-red-500">*</span>
        @endif
    </label>

    @if($type === 'textarea')
    <textarea name="{{ $name }}"
              placeholder="{{ $placeholder }}"
              class="border {{ $has_error ? 'border-red-500' : 'border-gray-300' }} rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition"
              @if($required) required @endif>{{ $field_value }}</textarea>
    @elseif($type === 'select')
    <select name="{{ $name }}"
            class="border {{ $has_error ? 'border-red-500' : 'border-gray-300' }} rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition bg-white"
            @if($required) required @endif>
        {{ $slot }}
    </select>
    @else
    <input type="{{ $type }}"
           name="{{ $name }}"
           placeholder="{{ $placeholder }}"
           value="{{ $field_value }}"
           class="border {{ $has_error ? 'border-red-500' : 'border-gray-300' }} rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition"
           @if($required) required @endif />
    @endif

    @if($has_error)
    <span class="text-xs text-red-600 flex items-center gap-1 mt-0.5">
        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18.101 12.93a.75.75 0 000-1.06l-5.455-5.455a.75.75 0 10-1.061 1.06L16.939 12l-4.854 4.854a.75.75 0 101.06 1.061l5.455-5.455zM9.101 12.93a.75.75 0 000-1.06L3.646 6.475a.75.75 0 00-1.06 1.06L7.939 12 3.085 16.854a.75.75 0 101.06 1.061l5.455-5.455z" clip-rule="evenodd"/>
        </svg>
        {{ $error_message }}
    </span>
    @elseif($help)
    <span class="text-xs text-gray-500">{{ $help }}</span>
    @endif
</div>
