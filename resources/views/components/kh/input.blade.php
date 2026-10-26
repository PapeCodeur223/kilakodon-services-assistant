@props([
    'name',
    'label',
    'type' => 'text',
    'id' => null,
    'hint' => null,
    'required' => false,
    'value' => null,
    'error' => null,
])
@php
    $id ??= 'f-'.str_replace(['.', '[', ']'], '-', $name);
    $error ??= $errors->first($name);
    $describedBy = collect([$hint ? $id.'-hint' : null, $error ? $id.'-error' : null])->filter()->implode(' ');
@endphp
{{-- Label toujours visible au-dessus ; erreur liée par aria-describedby (UX §9). --}}
<div {{ $attributes->only('class')->class('flex flex-col gap-1.5') }}>
    <label for="{{ $id }}" class="text-small font-medium text-ink">
        {{ $label }}
        @if ($required)
            <span class="text-kh-red-600" aria-hidden="true">*</span>
        @else
            <span class="font-normal text-slate">(facultatif)</span>
        @endif
    </label>
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        @if ($required) required aria-required="true" @endif
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->except('class')->class([
            'h-[52px] w-full rounded-input border bg-white px-4 text-body text-ink placeholder:text-smoke',
            'transition-colors duration-150 focus:outline-3 focus:outline-offset-2 focus:outline-kh-blue-500',
            'disabled:bg-mist disabled:text-slate',
            'border-line hover:border-kh-blue-200' => ! $error,
            'border-kh-red-600' => $error,
        ]) }}
    >
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
