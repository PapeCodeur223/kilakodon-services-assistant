{{-- Accueil provisoire (lot 0) : seul le hero de CONTENT.md §2. La page complète arrive au lot 1. --}}
<x-layouts.public>
    <section class="relative overflow-hidden">
        <x-kh.halftone :size="560" class="absolute -right-40 top-6 hidden lg:block" animate />
        <div class="kh-container relative flex min-h-[78vh] flex-col justify-center py-20">
            <x-kh.eyebrow data-reveal>Logiciels de gestion sur mesure — Bamako</x-kh.eyebrow>
            <h1 class="kh-hero-title mt-6 max-w-4xl text-display" data-reveal style="--i: 1">{{ \App\View\Support\Accent::html('Pilotez votre entreprise avec des outils *faits pour elle*.') }}</h1>
            <p class="kh-prose mt-6 text-lead text-slate" data-reveal style="--i: 2">Stock, caisse, clients, factures, équipes : nous concevons les logiciels qui vous donnent enfin une vision claire de votre activité — même quand la connexion coupe. Commencez par un diagnostic gratuit : en 3 minutes, vous saurez ce dont votre entreprise a vraiment besoin.</p>
            <div class="mt-10 flex flex-wrap items-center gap-3" data-reveal style="--i: 3">
                <x-kh.button :href="url('/diagnostic?entry=home')">Faire mon diagnostic gratuit</x-kh.button>
                <x-kh.button variant="ghost" :href="url('/demo-lab')">Essayer le Démo Lab</x-kh.button>
            </div>
            <p class="mt-4 text-small text-slate" data-reveal style="--i: 4">3 minutes · sans inscription · résultat immédiat</p>
        </div>
    </section>
</x-layouts.public>
