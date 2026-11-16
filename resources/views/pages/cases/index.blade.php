@php
    use App\Domain\Platform\Support\WhatsAppLink;
    use App\View\Support\Accent;
    use App\View\Support\StructuredData;

    /** @var \Illuminate\Support\Collection<int, \App\Domain\Content\Models\CaseStudy> $caseStudies */
    $crumbs = [['label' => 'Accueil', 'url' => route('home')], ['label' => 'Réalisations', 'url' => route('cases.index')]];
    $featured = $selected === null ? $caseStudies->first() : null;
    $rest = $featured ? $caseStudies->slice(1)->values() : $caseStudies;
    $chip = 'inline-flex min-h-11 items-center gap-2 rounded-full px-4 py-2 text-small font-semibold transition-colors focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-kh-blue-500';
@endphp
<x-layouts.public
    title="Réalisations"
    description="Des logiciels de gestion conçus par Kilakodon Services au Mali : contexte, défi, intervention et livrables, publiés avec l'autorisation écrite des clients."
    :schemas="[StructuredData::breadcrumbs($crumbs)]"
>
    <x-kh.page-hero eyebrow="Réalisations" title="Des projets menés *avec nos clients*" :breadcrumbs="$crumbs"
        lead="Écoles, commerces, santé, immobilier, production : des logiciels conçus pour le terrain. Chaque réalisation est publiée avec l'accord écrit du client concerné." />

    @if ($total > 0)
        {{-- Filtres par secteur : carte flottante qui chevauche l'en-tête, comme sur la page Méthode. --}}
        <nav aria-label="Filtrer par secteur" class="relative z-10 -mt-12 lg:-mt-16">
            <div class="kh-container">
                <div class="flex flex-wrap items-center gap-2 rounded-card bg-white p-4 shadow-pop lg:p-5" data-reveal="zoom">
                    <a href="{{ route('cases.index') }}#liste" @class([$chip, 'bg-kh-blue-700 text-white' => $selected === null, 'bg-fog text-ink hover:bg-kh-blue-50' => $selected !== null]) @if ($selected === null) aria-current="page" @endif>
                        <x-kh.icon name="layers" :size="18" /> Toutes <span @class(['rounded-full px-2 text-tag', 'bg-white/15' => $selected === null, 'bg-white' => $selected !== null])>{{ $total }}</span>
                    </a>
                    @foreach ($sectors as $sector)
                        @php $active = $selected === $sector['code']; @endphp
                        <a href="{{ route('cases.index', ['secteur' => $sector['code']]) }}#liste" @class([$chip, 'bg-kh-blue-700 text-white' => $active, 'bg-fog text-ink hover:bg-kh-blue-50' => ! $active]) @if ($active) aria-current="page" @endif>
                            <x-kh.icon :name="$sector['icon'] ?: 'layout-dashboard'" :size="18" /> {{ $sector['name'] }} <span @class(['rounded-full px-2 text-tag', 'bg-white/15' => $active, 'bg-white' => ! $active])>{{ $sector['count'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </nav>
    @endif

    <section id="liste" class="kh-section scroll-mt-24 bg-canvas" aria-label="Liste des réalisations">
        <div class="kh-container">
            @if ($caseStudies->isEmpty())
                <div class="kh-dark relative overflow-hidden rounded-[32px] px-6 py-16 text-center sm:px-12" data-reveal="zoom">
                    <div class="kh-grid-bg pointer-events-none absolute inset-0" aria-hidden="true"></div>
                    <div class="pointer-events-none absolute -right-16 -bottom-24" aria-hidden="true"><x-kh.halftone :size="320" tone="blue-900" class="kh-spin" /></div>
                    <div class="relative mx-auto max-w-2xl">
                        <span class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-white/10 text-white"><x-kh.icon name="shield-check" :size="32" /></span>
                        <h2 class="mt-6 text-h2 text-white">{{ Accent::html('Nos études de cas sont *en préparation*') }}</h2>
                        <p class="mt-4 text-lead text-kh-blue-200">Nous ne publions une réalisation qu'avec l'accord écrit du client. En attendant, essayez nos logiciels dans le Démo Lab ou parlons de votre projet.</p>
                        <div class="mt-8 flex flex-wrap justify-center gap-3">
                            <x-kh.button :href="route('diagnostic.start', ['entry' => 'realisations'])">Faire mon diagnostic gratuit</x-kh.button>
                            <x-kh.button variant="whatsapp" :href="WhatsAppLink::for('realisations')" />
                        </div>
                    </div>
                </div>
            @else
                <header class="mb-10 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <x-kh.eyebrow class="mb-4" data-reveal>{{ $caseStudies->count() }} {{ $caseStudies->count() > 1 ? 'réalisations' : 'réalisation' }}</x-kh.eyebrow>
                        <h2 class="text-h2 text-ink" data-reveal style="--i: 1">{{ Accent::html('Des logiciels pensés *pour chaque métier*') }}</h2>
                    </div>
                    @if ($selected)
                        <a href="{{ route('cases.index') }}#liste" class="inline-flex items-center gap-2 text-small font-semibold text-kh-blue-700 hover:underline"><x-kh.icon name="x" :size="16" /> Voir toutes les réalisations</a>
                    @endif
                </header>

                @if ($featured)
                    <div class="mb-6" data-reveal>
                        <x-kh.case-card :case-study="$featured" featured />
                    </div>
                @endif

                <ul class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($rest as $caseStudy)
                        <li data-reveal style="--i: {{ $loop->index % 6 }}"><x-kh.case-card :case-study="$caseStudy" /></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    {{-- Engagement de publication : ce que le visiteur peut attendre de ces pages. --}}
    <section class="kh-section bg-fog" aria-labelledby="engagement-title">
        <div class="kh-container grid items-center gap-10 lg:grid-cols-[1fr_1.3fr] lg:gap-16">
            <div>
                <x-kh.eyebrow class="mb-5" data-reveal>Notre engagement</x-kh.eyebrow>
                <h2 id="engagement-title" class="text-h2 text-ink" data-reveal style="--i: 1">{{ Accent::html('Des réalisations *vérifiables*, rien d’inventé') }}</h2>
            </div>
            <ul class="grid gap-4 sm:grid-cols-3">
                @foreach ([
                    ['icon' => 'shield-check', 'title' => 'Accord du client', 'text' => 'Publiée uniquement avec l’autorisation écrite du client.'],
                    ['icon' => 'user-round', 'title' => 'Anonymat respecté', 'text' => 'Le client peut rester anonyme : seul son secteur est indiqué.'],
                    ['icon' => 'badge-check', 'title' => 'Résultats vérifiés', 'text' => 'Aucun chiffre n’est affiché sans avoir été vérifié.'],
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
        title="Votre activité mérite *son logiciel*"
        text="Le diagnostic gratuit prépare notre premier échange : en 3 minutes, vos priorités et les modules adaptés."
        :primary-href="route('diagnostic.start', ['entry' => 'realisations'])"
        :whatsapp="WhatsAppLink::for('realisations', 'Bonjour Kilakodon, j\'ai vu vos réalisations et j\'aimerais un logiciel pour mon activité.')"
    />
</x-layouts.public>
