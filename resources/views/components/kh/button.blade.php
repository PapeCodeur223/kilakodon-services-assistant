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
        'lg' => 'min-h-14 px-7 py-4 text-[1.05rem]',
        'md' => 'min-h-12 px-6 py-3 text-base',
        'sm' => 'min-h-11 px-4 py-2 text-small',
    ];
    $variants = [
        'primary' => 'kh-btn-primary bg-kh-blue-700 text-white hover:bg-kh-blue-600',
        'ghost' => 'border-2 border-kh-blue-700/20 bg-white text-kh-blue-700 hover:border-kh-blue-700 hover:bg-kh-blue-50',
        'whatsapp' => 'border-2 border-kh-blue-700/20 bg-white text-kh-blue-900 hover:border-whatsapp hover:bg-kh-blue-50',
        'inverse' => 'kh-btn-primary bg-white text-kh-blue-900 hover:bg-kh-blue-50',
        'ghost-inverse' => 'border-2 border-white/25 text-white hover:border-white/60 hover:bg-white/10',
        'whatsapp-inverse' => 'border-2 border-white/25 text-white hover:border-whatsapp hover:bg-white/10',
    ];
@endphp
<{{ $tag }}
    @if ($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    @if ($isWhatsapp && $href) target="_blank" rel="noopener" @endif
    {{ $attributes->class([
        'kh-btn group inline-flex items-center justify-center gap-2.5 rounded-full font-semibold tracking-[-0.01em] transition-colors duration-200',
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
