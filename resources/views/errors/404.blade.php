<x-layouts.public title="Page introuvable" :noindex="true">
    <section class="kh-section relative overflow-hidden">
        <x-kh.halftone :size="420" class="absolute -right-32 top-8 hidden md:block" animate />
        <div class="kh-container relative">
            <x-kh.eyebrow>Erreur 404</x-kh.eyebrow>
            <h1 class="mt-4 max-w-2xl text-h1">Cette page n'existe pas <em>(ou plus)</em>.</h1>
            <p class="kh-prose mt-5 text-lead text-slate">Le lien est peut-être ancien ou mal recopié. Voici par où continuer :</p>
            <div class="mt-10 flex flex-wrap gap-3">
                <x-kh.button :href="url('/')">Retour à l'accueil</x-kh.button>
                <x-kh.button variant="ghost" :href="url('/solutions')">Voir nos solutions</x-kh.button>
                <x-kh.button variant="ghost" :href="url('/diagnostic?entry=404')">Faire le diagnostic</x-kh.button>
            </div>
        </div>
    </section>
</x-layouts.public>
