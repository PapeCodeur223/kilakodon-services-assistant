@php
    $crumbs = [
        ['label' => 'Accueil', 'url' => route('home')],
        ['label' => 'Démo Lab', 'url' => route('lab.index')],
        ['label' => 'Boutique', 'url' => route('lab.retail')],
    ];
@endphp
{{-- Démo interactive « Boutique » (LAB-01) : espace privé du visiteur, jamais indexé. --}}
<x-layouts.public
    title="Démo Boutique : caisse, stock et factures"
    description="Essayez un logiciel de caisse et de stock avec des données fictives : faites une vente, déclenchez une alerte de stock, imprimez une facture."
    :noindex="true"
>
    <x-kh.page-hero eyebrow="Démo Lab · Interactif" title="La boutique *Démo*" :breadcrumbs="$crumbs"
        lead="Caisse, stock, factures et tableau de bord d'une boutique fictive. 5 minutes suffisent." />

    <section class="relative z-10 -mt-12 pb-20 lg:-mt-16" aria-label="Application de démonstration">
        <div class="kh-container">
            <noscript>
                <p class="mb-4 rounded-card bg-white p-4 text-small text-slate shadow-pop">La démo interactive a besoin de JavaScript. Vous pouvez parcourir les données ci-dessous, ou <a class="font-semibold text-kh-blue-700 underline" href="{{ route('diagnostic.start', ['secteur' => 'retail', 'entry' => 'lab:retail']) }}">faire le diagnostic gratuit</a>.</p>
            </noscript>
            <livewire:demo-lab.shop :workspace="$workspace" />
        </div>
    </section>
</x-layouts.public>
