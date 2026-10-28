@props([
    'name',
    'id' => null,
    'required' => false,
])
@php
    $id ??= 'f-'.str_replace(['.', '[', ']'], '-', $name);
    $error = $errors->first($name);
@endphp
{{-- Jamais pré-cochée (règle non négociable n° 7) : aucun attribut `checked` par défaut. --}}
<div {{ $attributes->only('class')->class('flex flex-col gap-1.5') }}>
    <label for="{{ $id }}" class="flex cursor-pointer items-start gap-3 text-small text-ink">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="checkbox"
            value="1"
            @checked(old($name) === '1')
            @if ($required) required aria-required="true" @endif
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->whereStartsWith('wire:') }}
            class="mt-0.5 size-5 shrink-0 rounded border-line accent-kh-blue-700 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-kh-blue-500"
        >
        <span>{{ $slot }} @if ($required)<span class="text-kh-red-600" aria-hidden="true">*</span>@endif</span>
    </label>
    @if ($error)
        <p id="{{ $id }}-error" class="flex items-start gap-1.5 ps-8 text-small text-kh-red-600">
            <x-kh.icon name="circle-alert" :size="18" class="mt-0.5" />
            <span>{{ $error }}</span>
        </p>
    @endif
</div>
