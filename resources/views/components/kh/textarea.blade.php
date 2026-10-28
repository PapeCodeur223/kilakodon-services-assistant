@props([
    'name',
    'label',
    'id' => null,
    'hint' => null,
    'required' => false,
    'value' => null,
    'rows' => 5,
])
@php
    $id ??= 'f-'.str_replace(['.', '[', ']'], '-', $name);
    $error = $errors->first($name);
    $describedBy = collect([$hint ? $id.'-hint' : null, $error ? $id.'-error' : null])->filter()->implode(' ');
@endphp
<div {{ $attributes->only('class')->class('flex flex-col gap-1.5') }}>
    <label for="{{ $id }}" class="text-small font-medium text-ink">
        {{ $label }}
        @if ($required)
            <span class="text-kh-red-600" aria-hidden="true">*</span>
        @else
            <span class="font-normal text-slate">(facultatif)</span>
        @endif
    </label>
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($required) required aria-required="true" @endif
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->except('class')->class([
            'w-full rounded-input border bg-white px-4 py-3 text-body text-ink placeholder:text-smoke',
            'transition-colors duration-150 focus:outline-3 focus:outline-offset-2 focus:outline-kh-blue-500',
            'border-line hover:border-kh-blue-200' => ! $error,
            'border-kh-red-600' => $error,
        ]) }}
    >{{ old($name, $value) }}</textarea>
    @if ($hint)
        <p id="{{ $id }}-hint" class="text-small text-slate">{{ $hint }}</p>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="flex items-start gap-1.5 text-small text-kh-red-600">
            <x-kh.icon name="circle-alert" :size="18" class="mt-0.5" />
            <span>{{ $error }}</span>
        </p>
    @endif
</div>
