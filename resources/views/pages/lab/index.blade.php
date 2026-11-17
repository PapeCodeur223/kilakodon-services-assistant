@php
    use App\Domain\Platform\Support\WhatsAppLink;
    use App\View\Support\Accent;
    use App\View\Support\StructuredData;

    $crumbs = [['label' => 'Accueil', 'url' => route('home')], ['label' => 'Démo Lab', 'url' => route('lab.index')]];
@endphp
<x-layouts.public
    title="Le Démo Lab : testez nos logiciels"
    description="Testez nos logiciels comme si vous y étiez : une boutique interactive avec caisse, stock et factures fictives, et des visites guidées pour l'école et la boulangerie."
    :schemas="[StructuredData::breadcrumbs($crumbs)]"
>
    <x-kh.page-hero eyebrow="Démo Lab" title="Le *Démo Lab*" :breadcrumbs="$crumbs"
        lead="Testez nos logiciels comme si vous y étiez." />

    {{-- Les 3 démos (UX §5.6) : carte flottante qui chevauche l'en-tête. --}}
    <section class="relative z-10 -mt-12 lg:-mt-16" aria-labelledby="demos-title">
        <h2 id="demos-title" class="sr-only">Les démos disponibles</h2>
        <div class="kh-container">
            <ul class="grid gap-6 lg:grid-cols-[1.3fr_1fr_1fr]">
                <li data-reveal="zoom">
                    <a href="{{ route('lab.retail') }}" class="kh-card group flex h-full flex-col overflow-hidden rounded-card border border-line bg-white shadow-pop focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-kh-blue-500">
                        <div class="kh-dark relative flex h-48 items-end overflow-hidden p-6">
                            <div class="kh-grid-bg pointer-events-none absolute inset-0" aria-hidden="true"></div>
                            <div class="pointer-events-none absolute -right-10 -top-12 transition-transform duration-700 group-hover:rotate-12" aria-hidden="true"><x-kh.halftone :size="240" tone="blue-900" class="opacity-80" /></div>
                            <span class="relative flex size-16 items-center justify-center rounded-2xl bg-white/10 text-white ring-1 ring-white/20 transition-transform duration-300 group-hover:-translate-y-1"><x-kh.icon name="store" :size="32" /></span>
                        </div>
                        <div class="flex flex-1 flex-col p-7">
                            <div class="flex flex-wrap gap-2"><x-kh.badge tone="blue" icon="zap">Interactif</x-kh.badge><x-kh.badge>Données fictives</x-kh.badge></div>
                            <h3 class="mt-3 text-h2 text-ink">Boutique</h3>
                            <p class="mt-2 text-slate">Caisse, stock, factures et tableau de bord d'une boutique fictive. 5 minutes suffisent.</p>
                            <ul class="mt-5 flex flex-wrap gap-2 text-small text-ink">
                                @foreach (['Faites une vente', 'Déclenchez une alerte de stock', 'Imprimez une facture'] as $mission)
                                    <li class="inline-flex items-center gap-1.5 rounded-full bg-fog px-3 py-1"><x-kh.icon name="check" :size="14" class="text-kh-blue-700" />{{ $mission }}</li>
                                @endforeach
                            </ul>
                            <span class="mt-auto inline-flex items-center gap-1.5 pt-6 font-semibold text-kh-blue-700"><span class="kh-underline-grow">Ouvrir la démo Boutique</span><span class="kh-arrow" aria-hidden="true">→</span></span>
                        </div>
                    </a>
                </li>
                @foreach ($guided as $slug => $demo)
                    <li data-reveal="zoom" style="--i: {{ $loop->iteration }}">
                        <a href="{{ route('lab.guided', $slug) }}" class="kh-card group flex h-full flex-col rounded-card border border-line bg-white p-7 shadow-pop focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-kh-blue-500">
                            <span class="kh-card-icon flex size-14 items-center justify-center rounded-2xl bg-kh-blue-50 text-kh-blue-700 transition-transform"><x-kh.icon :name="$demo['icon']" :size="28" /></span>
                            <div class="mt-5 flex flex-wrap gap-2"><x-kh.badge icon="circle-play">Démo guidée</x-kh.badge><x-kh.badge tone="warning">Bientôt disponible</x-kh.badge></div>
                            <h3 class="mt-3 text-h3 text-ink">{{ $demo['title'] }}</h3>
                            <p class="mt-2 text-slate">{{ $demo['text'] }}</p>
                            <ul class="mt-5 space-y-2 text-small text-ink">
                                @foreach ($demo['topics'] as $topic)
                                    <li class="flex items-start gap-2"><x-kh.icon name="check" :size="16" class="mt-0.5 shrink-0 text-kh-blue-700" />{{ $topic }}</li>
                                @endforeach
                            </ul>
                            <span class="mt-auto inline-flex items-center gap-1.5 pt-6 font-semibold text-kh-blue-700"><span class="kh-underline-grow">Voir la démo guidée</span><span class="kh-arrow" aria-hidden="true">→</span></span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Comment ça marche --}}
    <section class="kh-section bg-canvas" aria-labelledby="comment-title">
        <div class="kh-container grid items-center gap-10 lg:grid-cols-[1fr_1.3fr] lg:gap-16">
            <div>
                <x-kh.eyebrow class="mb-5" data-reveal>Comment ça marche</x-kh.eyebrow>
                <h2 id="comment-title" class="text-h2 text-ink" data-reveal style="--i: 1">{{ Accent::html('Ne nous croyez pas sur parole : *essayez*') }}</h2>
                <p class="mt-4 text-lead text-slate" data-reveal style="--i: 2">Faites une vente, regardez le stock baisser, déclenchez une alerte, imprimez une facture. Le Démo Lab est un vrai logiciel, avec des données fictives, que vous pouvez manipuler librement.</p>
            </div>
            <ul class="grid gap-4 sm:grid-cols-3">
                @foreach ([
                    ['icon' => 'user-round', 'title' => 'Votre espace', 'text' => 'Chaque visiteur a sa propre boutique : personne d’autre ne voit vos essais.'],
                    ['icon' => 'flask-conical', 'title' => 'Données fictives', 'text' => 'Produits, clients et prix sont inventés pour la démonstration et marqués « Démo ».'],
                    ['icon' => 'clock', 'title' => 'Réinitialisé en 24 h', 'text' => 'Votre espace et tout ce que vous y faites sont effacés après 24 heures.'],
                ] as $i => $item)
                    <li class="rounded-card border border-line bg-white p-6" data-reveal style="--i: {{ $i }}">
                        <span class="inline-flex size-11 items-center justify-center rounded-2xl bg-kh-blue-50 text-kh-blue-700"><x-kh.icon :name="$item['icon']" :size="22" /></span>
                        <h3 class="mt-4 font-semibold text-ink">{{ $item['title'] }}</h3>
                        <p class="mt-1 text-small text-slate">{{ $item['text'] }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <x-kh.cta-band
        title="Vous voulez ça pour *votre entreprise* ?"
        text="Le diagnostic gratuit vous indique en 3 minutes les modules adaptés à votre activité."
        :primary-href="route('diagnostic.start', ['entry' => 'lab'])"
        :whatsapp="WhatsAppLink::forDemo()"
    />
</x-layouts.public>
