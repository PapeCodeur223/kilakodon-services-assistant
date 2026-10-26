@props([
    'title' => 'Votre prochain logiciel commence par *une bonne question*',
    'text' => '3 minutes pour clarifier votre besoin. Un conseiller pour en parler.',
    'primaryLabel' => 'Faire mon diagnostic gratuit',
    'primaryHref' => null,
    'whatsapp' => null,
    'id' => 'cta-finale',
])
{{-- Bande CTA finale (UX §5.1) : fond kh-blue-950, bouton principal inversé + WhatsApp. --}}
<x-kh.section tone="blue" :id="$id" :title="$title" :lead="$text">
    <x-slot:decor>
        <x-kh.halftone :size="360" tone="blue-900" class="absolute -right-20 -bottom-24" />
    </x-slot:decor>
    <div class="flex flex-wrap gap-3" data-reveal style="--i: 3">
        <x-kh.button variant="inverse" :href="$primaryHref ?? url('/diagnostic?entry=cta')">{{ $primaryLabel }}</x-kh.button>
        <x-kh.button variant="whatsapp-inverse" :href="$whatsapp ?? \App\Domain\Platform\Support\WhatsAppLink::for('cta')" />
    </div>
</x-kh.section>
