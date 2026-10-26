@props(['demo' => true, 'label' => null, 'float' => false, 'i' => 0])
{{-- Fragment de produit. Tout chiffre affiché porte l'étiquette « Démo » (UX §4). --}}
<div {{ $attributes->class(['rounded-artifact bg-white px-5 py-4 text-small text-ink shadow-artifact', 'kh-float' => $float]) }} @if ($float) style="--i: {{ $i }}; --float-duration: {{ 6 + ($i % 3) }}s" @endif>
    @if ($label || $demo)
        <div class="mb-2 flex items-center justify-between gap-3">
            @if ($label)
                <p class="text-tag font-medium uppercase text-slate">{{ $label }}</p>
            @endif
            @if ($demo)
                <x-kh.badge tone="neutral" class="ms-auto">Démo</x-kh.badge>
            @endif
        </div>
    @endif
    {{ $slot }}
</div>
