@props([
    'name',
    'label',
    'options',
    'id' => null,
    'required' => false,
    'value' => null,
    'placeholder' => 'Choisissez…',
])
@php
    $id ??= 'f-'.str_replace(['.', '[', ']'], '-', $name);
    $error = $errors->first($name);
    $selected = (string) old($name, $value);
@endphp
<div {{ $attributes->only('class')->class('flex flex-col gap-1.5') }}>
    <label for="{{ $id }}" class="text-small font-medium text-ink">
        {{ $label }}
        @if ($required)
            <span class="text-kh-red-600" aria-hidden="true">*</span>
        @endif
    </label>
    <div class="relative">
        <select
            id="{{ $id }}"
            name="{{ $name }}"
            @if ($required) required aria-required="true" @endif
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            @class([
                'h-[52px] w-full appearance-none rounded-input border bg-white ps-4 pe-11 text-body text-ink',
                'transition-colors duration-150 focus:outline-3 focus:outline-offset-2 focus:outline-kh-blue-500',
                'border-line hover:border-kh-blue-200' => ! $error,
                'border-kh-red-600' => $error,
            ])
        >
            <option value="" @selected($selected === '') disabled>{{ $placeholder }}</option>
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
        <x-kh.icon name="chevron-down" :size="18" class="pointer-events-none absolute end-4 top-1/2 -translate-y-1/2 text-slate" />
    </div>
    @if ($error)
        <p id="{{ $id }}-error" class="flex items-start gap-1.5 text-small text-kh-red-600">
            <x-kh.icon name="circle-alert" :size="18" class="mt-0.5" />
            <span>{{ $error }}</span>
        </p>
    @endif
</div>
