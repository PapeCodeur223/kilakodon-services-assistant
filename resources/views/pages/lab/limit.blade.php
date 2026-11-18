@php use App\Domain\Platform\Support\WhatsAppLink; @endphp
{{-- Limite de 3 espaces par jour atteinte (TRD §8) : on oriente vers la suite plutôt qu'une erreur sèche. --}}
<x-layouts.public title="Démo Boutique : limite du jour atteinte" :noindex="true">
    <x-kh.page-hero eyebrow="Démo Lab" title="Vous avez bien *exploré* la démo" :breadcrumbs="[['label' => 'Accueil', 'url' => route('home')], ['label' => 'Démo Lab', 'url' => route('lab.index')]]"
        lead="Vous avez ouvert le nombre maximal d'espaces de démonstration pour aujourd'hui. Vous pourrez en ouvrir un nouveau demain." />
    <section class="kh-section bg-canvas">
        <div class="kh-container max-w-2xl text-center">
            <p class="text-lead text-slate">Le plus utile maintenant : parler de votre boutique à vous.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <x-kh.button :href="route('diagnostic.start', ['secteur' => 'retail', 'entry' => 'lab:retail'])">Faire mon diagnostic gratuit</x-kh.button>
                <x-kh.button variant="whatsapp" :href="WhatsAppLink::forDemo()" />
            </div>
        </div>
    </section>
</x-layouts.public>
