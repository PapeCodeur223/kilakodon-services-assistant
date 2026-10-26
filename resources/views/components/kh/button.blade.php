@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'button',
    'size' => 'md',
    'arrow' => null,
])
@php
    $arrow ??= in_array($variant, ['primary', 'inverse'], true);
    $isWhatsapp = str_starts_with($variant, 'whatsapp');
    $tag = $href ? 'a' : 'button';
    $sizes = [
        'md' => 'min-h-12 px-[22px] py-3 text-base',
        'sm' => 'min-h-11 px-4 py-2 text-small font-semibold',
    ];
    $variants = [
        'primary' => 'bg-kh-blue-700 text-white hover:bg-kh-blue-900',
        'ghost' => 'border border-kh-blue-700 text-kh-blue-700 hover:bg-kh-blue-50',
        'whatsapp' => 'border border-kh-blue-700 text-kh-blue-700 hover:bg-kh-blue-50',
        'inverse' => 'bg-white text-kh-blue-700 hover:bg-kh-blue-50',
        'ghost-inverse' => 'border border-white/70 text-white hover:bg-white/10',
        'whatsapp-inverse' => 'border border-white/70 text-white hover:bg-white/10',
    ];
@endphp
<{{ $tag }}
    @if ($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    @if ($isWhatsapp && $href) target="_blank" rel="noopener" @endif
    {{ $attributes->class([
        'group inline-flex items-center justify-center gap-2 rounded-full font-medium transition-colors duration-150',
        'focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-kh-blue-500',
        'disabled:cursor-not-allowed disabled:opacity-50',
        $sizes[$size] ?? $sizes['md'],
        $variants[$variant] ?? $variants['primary'],
    ]) }}
>
    @if ($isWhatsapp)
        <x-kh.icon name="brand:whatsapp" :size="20" class="text-whatsapp" />
    @endif
    <span>{{ $slot->isEmpty() && $isWhatsapp ? 'Discuter sur WhatsApp' : $slot }}</span>
    @if ($arrow)
        <span class="kh-arrow" aria-hidden="true">→</span>
    @endif
</{{ $tag }}>
