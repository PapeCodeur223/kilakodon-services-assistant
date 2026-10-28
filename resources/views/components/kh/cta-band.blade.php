@props([
    'title' => 'Votre prochain logiciel commence par *une bonne question*',
    'text' => '3 minutes pour clarifier votre besoin. Un conseiller pour en parler.',
    'primaryLabel' => 'Faire mon diagnostic gratuit',
    'primaryHref' => null,
    'whatsapp' => null,
    'id' => 'cta-finale',
])
{{-- Bande CTA finale : carte sombre « agence » avec sphère en rotation et halo. --}}
<section id="{{ $id }}" class="kh-section bg-canvas" aria-labelledby="{{ $id }}-title">
    <div class="kh-container">
        <div class="kh-dark relative overflow-hidden rounded-[32px] px-6 py-16 sm:px-12 lg:px-20 lg:py-24" data-reveal="zoom">
            <div class="kh-grid-bg pointer-events-none absolute inset-0" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -right-20 -bottom-28 hidden sm:block" aria-hidden="true">
                <x-kh.halftone :size="420" tone="blue-900" class="kh-spin" />
            </div>
            <div class="relative max-w-2xl">
                <h2 id="{{ $id }}-title" class="text-h2 text-white">{{ \App\View\Support\Accent::html($title) }}</h2>
                <p class="mt-5 text-lead text-kh-blue-200">{{ $text }}</p>
                <div class="mt-10 flex flex-wrap gap-3">
                    <x-kh.button variant="inverse" size="lg" :href="$primaryHref ?? url('/diagnostic?entry=cta')">{{ $primaryLabel }}</x-kh.button>
                    <x-kh.button variant="whatsapp-inverse" size="lg" :href="$whatsapp ?? \App\Domain\Platform\Support\WhatsAppLink::for('cta')" />
                </div>
            </div>
        </div>
    </div>
</section>
