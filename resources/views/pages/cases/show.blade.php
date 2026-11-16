@php
    use App\Domain\Content\Enums\ClientDisplay;
    use App\Domain\Platform\Support\WhatsAppLink;
    use App\View\Support\Accent;
    use App\View\Support\StructuredData;

    /** @var \App\Domain\Content\Models\CaseStudy $caseStudy */
    $crumbs = [
        ['label' => 'Accueil', 'url' => route('home')],
        ['label' => 'Réalisations', 'url' => route('cases.index')],
        ['label' => $caseStudy->title, 'url' => $caseStudy->publicUrl()],
    ];
    $sector = $caseStudy->sector;
    $story = array_values(array_filter([
        ['key' => 'contexte', 'title' => 'Contexte', 'icon' => 'map-pin', 'text' => $caseStudy->context],
        ['key' => 'defi', 'title' => 'Défi', 'icon' => 'flag', 'text' => $caseStudy->challenge],
        ['key' => 'intervention', 'title' => 'Notre intervention', 'icon' => 'code-xml', 'text' => $caseStudy->intervention],
    ], fn (array $part): bool => filled($part['text'])));
    $facts = array_values(array_filter([
        $sector ? ['icon' => $sector->icon ?: 'building-2', 'label' => 'Secteur', 'value' => $sector->name] : null,
        ['icon' => 'user-round', 'label' => $caseStudy->client_display === ClientDisplay::Named ? 'Client' : 'Client (anonymisé)', 'value' => $caseStudy->client_display === ClientDisplay::Named && filled($caseStudy->client_name) ? (string) $caseStudy->client_name : $caseStudy->client_descriptor],
        $caseStudy->services->isNotEmpty() ? ['icon' => 'layers', 'label' => 'Solutions', 'value' => $caseStudy->services->pluck('title')->implode(' · ')] : null,
        $caseStudy->deliverables !== [] ? ['icon' => 'file-check', 'label' => 'Livrables', 'value' => count($caseStudy->deliverables).' livrables'] : null,
    ]));
@endphp
<x-layouts.public
    :title="$caseStudy->seo_title ?: $caseStudy->title"
    :description="$caseStudy->seo_description ?: \Illuminate\Support\Str::limit(strip_tags($caseStudy->context), 158)"
    :schemas="[StructuredData::breadcrumbs($crumbs)]"
>
    <x-kh.page-hero eyebrow="Réalisation" :title="$caseStudy->title" :lead="$caseStudy->seo_description" :breadcrumbs="$crumbs" :status="$caseStudy->status" />

    {{-- Fiche d'identité : carte flottante qui chevauche l'en-tête. --}}
    <section aria-label="La réalisation en bref" class="relative z-10 -mt-12 lg:-mt-16">
        <div class="kh-container">
            <dl class="grid gap-3 rounded-card bg-white p-4 shadow-pop sm:grid-cols-2 lg:p-6 {{ count($facts) >= 4 ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }}" data-reveal="zoom">
                @foreach ($facts as $i => $fact)
                    <div @class(['flex items-start gap-4 rounded-input p-3', 'lg:border-s lg:border-line lg:ps-6' => $i > 0])>
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-kh-blue-50 text-kh-blue-700"><x-kh.icon :name="$fact['icon']" :size="22" /></span>
                        <div class="min-w-0">
                            <dt class="text-tag font-semibold uppercase text-slate">{{ $fact['label'] }}</dt>
                            <dd class="mt-0.5 font-semibold leading-snug text-ink [overflow-wrap:anywhere]">{{ $fact['value'] }}</dd>
                        </div>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- Le récit : Contexte → Défi → Intervention (UX §5.7), en frise comme la page Méthode. --}}
    @if ($story !== [])
        <section class="kh-section bg-canvas" aria-labelledby="recit-title">
            <div class="kh-container">
                <header class="mx-auto mb-14 max-w-2xl text-center">
                    <x-kh.eyebrow class="mb-5" data-reveal>Le projet</x-kh.eyebrow>
                    <h2 id="recit-title" class="text-h2 text-ink" data-reveal style="--i: 1">{{ Accent::html('Du besoin du terrain *au logiciel*') }}</h2>
                </header>

                <ol class="relative mx-auto max-w-4xl">
                    <span class="pointer-events-none absolute top-0 bottom-0 left-6 w-0.5 bg-linear-to-b from-kh-blue-700 via-kh-blue-300 to-kh-red-400" aria-hidden="true"></span>
                    @foreach ($story as $i => $part)
                        <li id="{{ $part['key'] }}" class="relative pb-10 ps-20 last:pb-0">
                            <span class="absolute top-2 left-0 z-10 flex size-12 items-center justify-center rounded-full bg-kh-blue-700 text-white shadow-glow ring-8 ring-canvas" aria-hidden="true"><x-kh.icon :name="$part['icon']" :size="22" /></span>
                            <article class="kh-card rounded-card border border-line bg-white p-7 sm:p-9" data-reveal="left">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-tag font-semibold uppercase text-kh-blue-700">Étape {{ $i + 1 }} sur {{ count($story) }}</p>
                                        <h3 class="mt-2 text-h3 text-ink">{{ $part['title'] }}</h3>
                                    </div>
                                    <span class="font-display text-[3rem] leading-none font-extrabold text-kh-blue-50 select-none" aria-hidden="true">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                </div>
                                <x-kh.markdown :content="$part['text']" class="mt-4 text-body text-slate" />
                            </article>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    @if ($caseStudy->deliverables !== [])
        <section class="kh-section bg-fog" aria-labelledby="livrables-title">
            <div class="kh-container">
                <header class="mb-12 max-w-2xl">
                    <x-kh.eyebrow class="mb-5" data-reveal>Livrables</x-kh.eyebrow>
                    <h2 id="livrables-title" class="text-h2 text-ink" data-reveal style="--i: 1">{{ Accent::html('Ce que le logiciel *apporte au quotidien*') }}</h2>
                </header>
                <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($caseStudy->deliverables as $i => $deliverable)
                        <li class="kh-card relative overflow-hidden rounded-card border border-line bg-white p-6" data-reveal style="--i: {{ $i % 6 }}">
                            <span class="font-display text-[2.5rem] leading-none font-extrabold text-kh-blue-50 select-none" aria-hidden="true">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            <p class="mt-3 flex items-start gap-2 font-semibold text-ink"><x-kh.icon name="circle-check" :size="20" class="mt-0.5 shrink-0 text-kh-blue-700" />{{ $deliverable }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if ($caseStudy->showsResults() || $caseStudy->showsQuote())
        <section class="kh-section kh-dark relative overflow-hidden" aria-label="Résultats et témoignage">
            <div class="kh-grid-bg pointer-events-none absolute inset-0" aria-hidden="true"></div>
            <div class="kh-container relative grid gap-12 lg:grid-cols-2 lg:gap-16">
                @if ($caseStudy->showsResults())
                    <div data-reveal="left">
                        <x-kh.eyebrow tone="inverse" class="mb-5">Résultats vérifiés</x-kh.eyebrow>
                        <x-kh.markdown :content="$caseStudy->results" class="text-lead text-kh-blue-200" />
                    </div>
                @endif
                @if ($caseStudy->showsQuote())
                    <figure class="rounded-card bg-white/5 p-8 ring-1 ring-white/10" data-reveal="right">
                        <x-kh.icon name="quote" :size="32" class="text-kh-blue-200" />
                        <blockquote class="mt-4 font-display text-h3 font-extrabold text-white">« {{ $caseStudy->quote }} »</blockquote>
                        @if ($caseStudy->quote_author)<figcaption class="mt-4 text-small text-kh-blue-200">— {{ $caseStudy->quote_author }}</figcaption>@endif
                    </figure>
                @endif
            </div>
        </section>
    @endif

    @if ($caseStudy->services->isNotEmpty())
        <section class="kh-section bg-canvas" aria-labelledby="solutions-title">
            <div class="kh-container">
                <header class="mb-12 max-w-2xl">
                    <x-kh.eyebrow class="mb-5" data-reveal>Solutions utilisées</x-kh.eyebrow>
                    <h2 id="solutions-title" class="text-h2 text-ink" data-reveal style="--i: 1">{{ Accent::html('Les mêmes solutions, *pour votre activité*') }}</h2>
                </header>
                <ul class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($caseStudy->services as $i => $service)
                        <li data-reveal style="--i: {{ $i }}">
                            <x-kh.card :href="$service->publicUrl()" :icon="$service->icon" :title="$service->title" class="h-full">
                                {{ $service->tagline }}
                                <x-slot:footer><span class="font-medium text-kh-blue-700">Découvrir la solution <span class="kh-arrow" aria-hidden="true">→</span></span></x-slot:footer>
                            </x-kh.card>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if ($related->isNotEmpty())
        <section class="kh-section bg-fog" aria-labelledby="autres-title">
            <div class="kh-container">
                <header class="mb-12 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <x-kh.eyebrow class="mb-5" data-reveal>Autres réalisations</x-kh.eyebrow>
                        <h2 id="autres-title" class="text-h2 text-ink" data-reveal style="--i: 1">{{ Accent::html('D’autres métiers, *la même exigence*') }}</h2>
                    </div>
                    <x-kh.link-arrow :href="route('cases.index')">Toutes les réalisations</x-kh.link-arrow>
                </header>
                <ul class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $other)
                        <li data-reveal style="--i: {{ $loop->index }}"><x-kh.case-card :case-study="$other" as="h3" /></li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <x-kh.cta-band
        title="Un projet *similaire* ?"
        text="Le diagnostic gratuit vous indique en 3 minutes les modules adaptés à votre entreprise."
        :primary-href="route('diagnostic.start', ['entry' => 'realisation:'.$caseStudy->slug])"
        :whatsapp="WhatsAppLink::for('realisation', 'Bonjour Kilakodon, j\'ai vu votre réalisation « '.$caseStudy->title.' » et j\'aimerais un projet similaire.')"
    />
</x-layouts.public>
