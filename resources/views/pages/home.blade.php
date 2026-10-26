@php
    use App\Domain\Platform\Support\WhatsAppLink;
    use App\View\Support\Accent;
    use App\View\Support\StructuredData;

    $faqItems = $faqs->map(fn ($faq) => ['q' => $faq->question, 'a' => $faq->answer])->all();
@endphp
<x-layouts.public :schemas="[StructuredData::organization(), StructuredData::faqPage($faqItems)]">

    {{-- HERO --}}
    <section class="relative overflow-hidden">
        <x-kh.halftone :size="620" class="absolute -right-48 top-0 hidden lg:block" animate />
        <div class="kh-container relative grid items-center gap-12 py-14 lg:min-h-[86vh] lg:grid-cols-[1.25fr_1fr] lg:py-20">
            <div>
                <x-kh.eyebrow data-reveal>{{ $home['hero']['eyebrow'] }}</x-kh.eyebrow>
                <h1 class="kh-hero-title mt-6 text-display text-ink" data-reveal style="--i: 1">{{ Accent::html($home['hero']['title']) }}</h1>
                <p class="kh-prose mt-6 text-lead text-slate" data-reveal style="--i: 2">{{ $home['hero']['lead'] }}</p>
                <div class="mt-10 flex flex-wrap items-center gap-3" data-reveal style="--i: 3">
                    <x-kh.button :href="url('/diagnostic?entry=home')">{{ $home['hero']['cta_primary'] }}</x-kh.button>
                    <x-kh.button variant="ghost" :href="url('/demo-lab')">{{ $home['hero']['cta_secondary'] }}</x-kh.button>
                </div>
                <p class="mt-4 text-small text-slate" data-reveal style="--i: 4">{{ $home['hero']['reassurance'] }}</p>
            </div>

            {{-- Artefacts : flottants sur desktop, défilement horizontal doux sur mobile. Données fictives étiquetées « Démo ». --}}
            <div class="-mx-5 flex snap-x gap-4 overflow-x-auto px-5 pb-6 lg:mx-0 lg:grid lg:grid-cols-2 lg:gap-5 lg:overflow-visible lg:px-0 lg:pb-0" aria-label="Exemples d'écrans (données de démonstration)" role="region" tabindex="0">
                <x-kh.artifact label="Brief" float :i="0" class="w-72 shrink-0 snap-start lg:col-span-2 lg:w-auto lg:max-w-sm">
                    <p class="font-semibold">KLK-2026-0042</p>
                    <p class="mt-1 text-slate">Boutique, 3 points de vente</p>
                    <p class="mt-3 text-tag font-medium uppercase text-slate">Modules recommandés</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <x-kh.badge tone="blue">Stock &amp; caisse</x-kh.badge>
                        <x-kh.badge tone="blue">Multi-sites</x-kh.badge>
                        <x-kh.badge tone="blue">Mobile Money</x-kh.badge>
                    </div>
                </x-kh.artifact>
                <x-kh.artifact label="Alerte stock" float :i="1" class="w-64 shrink-0 snap-start lg:w-auto lg:translate-x-6">
                    <p class="flex items-start gap-2">
                        <x-kh.icon name="triangle-alert" :size="18" class="mt-0.5 text-kh-red-600" />
                        <span><strong class="font-semibold">Riz 25 kg</strong> : 4 sacs restants (seuil 10)</span>
                    </p>
                </x-kh.artifact>
                <x-kh.artifact label="Ventes 7 jours" float :i="2" class="w-64 shrink-0 snap-start lg:w-auto">
                    <svg viewBox="0 0 160 48" class="h-12 w-full text-kh-blue-500" aria-hidden="true" focusable="false">
                        <polyline fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" points="0,40 26,32 52,36 78,22 104,26 130,12 160,8" />
                    </svg>
                </x-kh.artifact>
                <x-kh.artifact :demo="false" float :i="3" class="w-72 shrink-0 snap-start lg:col-span-2 lg:w-auto lg:max-w-sm lg:justify-self-end">
                    <div class="flex items-center gap-2 rounded-input border border-line px-3 py-2">
                        <span class="flex-1 text-slate">Quel logiciel pour ma boutique ?</span>
                        <span class="inline-flex size-8 items-center justify-center rounded-full bg-kh-blue-700 text-white"><x-kh.icon name="arrow-up" :size="16" /></span>
                    </div>
                </x-kh.artifact>
            </div>
        </div>
    </section>

    {{-- RÉALITÉS LOCALES --}}
    @if ($home['realities']['items'] !== [])
        <section class="bg-fog py-12 lg:py-16" aria-labelledby="realites-title">
            <div class="kh-container">
                <h2 id="realites-title" class="font-sans text-h3 font-medium text-ink" data-reveal>{{ $home['realities']['title'] }}</h2>
                <ul class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($home['realities']['items'] as $item)
                        <li class="flex gap-4" data-reveal style="--i: {{ $loop->index }}">
                            <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-kh-blue-100 text-kh-blue-700"><x-kh.icon :name="$item['icon']" :size="22" /></span>
                            <div>
                                <p class="font-medium text-ink">{{ $item['title'] }}</p>
                                <p class="mt-1 text-small text-slate">{{ $item['text'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- VOUS VOUS RECONNAISSEZ ? --}}
    <x-kh.section id="reconnaitre" eyebrow="Vous vous reconnaissez ?" :title="$home['recognize']['title']">
        <div class="grid gap-5 md:grid-cols-2">
            @foreach ($home['recognize']['items'] as $item)
                @php $solution = $services->firstWhere('slug', $item['solution']); @endphp
                <x-kh.card :href="$solution?->publicUrl()" :icon="$item['icon']" data-reveal style="--i: {{ $loop->index }}">
                    <p class="font-display text-h3 text-ink">« {{ $item['quote'] }} »</p>
                    <p class="mt-3">{{ $item['answer'] }}</p>
                    <x-slot:footer>
                        <span class="font-medium text-kh-blue-700">{{ $item['solution_label'] }}@if ($solution) <span aria-hidden="true">→</span>@endif</span>
                    </x-slot:footer>
                </x-kh.card>
            @endforeach
        </div>
    </x-kh.section>

    {{-- SOLUTIONS --}}
    @if ($services->isNotEmpty())
        <x-kh.section id="solutions" tone="fog" eyebrow="Solutions" :title="$home['solutions']['title']" :lead="$home['solutions']['lead']">
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $service)
                    <x-kh.card :href="$service->publicUrl()" :icon="$service->icon" :title="$service->title" data-reveal style="--i: {{ min($loop->index, 6) }}">
                        {{ $service->tagline }}
                        <x-slot:footer>
                            <span class="font-medium text-ink">Découvrir <span aria-hidden="true">→</span></span>
                            <x-kh.status-marker :status="$service->status" />
                        </x-slot:footer>
                    </x-kh.card>
                @endforeach
            </div>
        </x-kh.section>
    @endif

    {{-- DIAGNOSTIC --}}
    <x-kh.section id="diagnostic" :eyebrow="$home['diagnostic']['eyebrow']" :title="$home['diagnostic']['title']">
        <div class="grid items-center gap-12 lg:grid-cols-2">
            <div>
                <ol class="space-y-8">
                    @foreach ($home['diagnostic']['steps'] as $step)
                        <li class="flex gap-5" data-reveal style="--i: {{ $loop->index }}">
                            <span class="font-display text-h2 leading-none text-kh-blue-700" aria-hidden="true">{{ $loop->iteration }}</span>
                            <div>
                                <h3 class="text-h3 font-medium text-ink">{{ $step['title'] }}</h3>
                                <p class="mt-2 text-slate">{{ $step['text'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
                <div class="mt-10" data-reveal style="--i: 3">
                    <x-kh.button :href="url('/diagnostic?entry=home')">{{ $home['diagnostic']['cta'] }}</x-kh.button>
                    <p class="mt-3 text-small text-slate">{{ $home['diagnostic']['note'] }}</p>
                </div>
            </div>
            <x-kh.artifact label="Votre diagnostic" data-reveal style="--i: 2" class="lg:ms-auto lg:max-w-md">
                <div class="flex items-center gap-2" aria-hidden="true">
                    @for ($i = 1; $i <= 4; $i++)
                        <span @class(['size-3 rounded-full', 'bg-kh-blue-700' => $i <= 2, 'bg-kh-blue-100' => $i > 2])></span>
                    @endfor
                </div>
                <p class="mt-2 font-display text-h3">Niveau 2 sur 4 · Outils dispersés</p>
                <p class="mt-4 text-tag font-medium uppercase text-slate">Ce que nous recommandons</p>
                <ul class="mt-2 space-y-2">
                    @foreach (['Stock & caisse' => 'Vous avez signalé des écarts de stock.', 'Multi-sites' => 'Vous avez 3 points de vente.', 'Tableau de bord' => 'Vos rapports sont faits à la main.'] as $module => $why)
                        <li class="rounded-input bg-mist px-3 py-2">
                            <p class="font-medium">{{ $module }}</p>
                            <p class="text-small text-slate"><span class="font-medium text-kh-blue-700">Pourquoi ?</span> {{ $why }}</p>
                        </li>
                    @endforeach
                </ul>
            </x-kh.artifact>
        </div>
    </x-kh.section>

    {{-- DÉMO LAB --}}
    <x-kh.section id="demo-lab" tone="fog" eyebrow="Démo Lab" :title="$home['lab']['title']" :lead="$home['lab']['text']">
        <div class="grid items-center gap-10 lg:grid-cols-[1fr_1.2fr]">
            <div data-reveal>
                <x-kh.button :href="url('/demo-lab/boutique')">{{ $home['lab']['cta'] }}</x-kh.button>
            </div>
            {{-- Aperçu statique (la capture animée sera fournie avec le lot 5). --}}
            <x-kh.artifact label="Démo Boutique — caisse" data-reveal style="--i: 1">
                <div class="grid grid-cols-3 gap-2 text-center text-small">
                    @foreach (['Riz 25 kg', 'Huile 5 L', 'Sucre 1 kg', 'Savon', 'Lait en poudre', 'Crédit tél.'] as $product)
                        <span class="rounded-input bg-mist px-2 py-3">{{ $product }}</span>
                    @endforeach
                </div>
                <div class="mt-3 flex items-center justify-between rounded-input bg-kh-blue-50 px-3 py-2 text-kh-blue-900">
                    <span>Panier : 3 articles</span>
                    <span class="font-semibold">Encaisser</span>
                </div>
            </x-kh.artifact>
        </div>
    </x-kh.section>

    {{-- MÉTHODE --}}
    <x-kh.section id="methode" eyebrow="Méthode" :title="$home['method']['title']">
        <div class="relative" data-reveal>
            <span class="kh-method-line absolute top-5 right-0 left-0 hidden h-px bg-kh-blue-200 lg:block" aria-hidden="true"></span>
            <ol class="relative grid gap-8 lg:grid-cols-4">
                @foreach ($home['method']['steps'] as $step)
                    <li data-reveal style="--i: {{ $loop->index }}">
                        <span class="relative inline-flex size-10 items-center justify-center rounded-full bg-kh-blue-700 font-semibold text-white">{{ $loop->iteration }}</span>
                        <h3 class="mt-5 text-h3 font-medium text-ink">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-slate">{{ $step['text'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
        @if ($methodPageVisible)
            <div class="mt-10"><x-kh.link-arrow :href="route('pages.method')">{{ $home['method']['cta'] }}</x-kh.link-arrow></div>
        @endif
    </x-kh.section>

    {{-- RÉALISATIONS : masquée tant qu'aucune réalisation autorisée n'est publiée --}}
    @if ($caseStudies->isNotEmpty())
        <x-kh.section id="realisations" tone="fog" eyebrow="Réalisations" title="Ils nous ont fait *confiance*">
            <div class="grid gap-5 md:grid-cols-3">
                @foreach ($caseStudies as $caseStudy)
                    <x-kh.card :href="$caseStudy->publicUrl()" :title="$caseStudy->title" data-reveal style="--i: {{ $loop->index }}">
                        {{ $caseStudy->clientLabel() }}
                        <x-slot:footer>
                            @if ($caseStudy->sector)<x-kh.badge tone="blue">{{ $caseStudy->sector->name }}</x-kh.badge>@endif
                            <x-kh.status-marker :status="$caseStudy->status" />
                        </x-slot:footer>
                    </x-kh.card>
                @endforeach
            </div>
        </x-kh.section>
    @endif

    {{-- POURQUOI KILAKODON --}}
    <x-kh.section id="pourquoi" :tone="$caseStudies->isNotEmpty() ? 'canvas' : 'fog'" eyebrow="Pourquoi Kilakodon">
        <div class="grid gap-5 lg:grid-cols-2">
            <x-kh.card-accent tone="red" :title="$home['why']['accent']['title']" as="h2" data-reveal class="lg:row-span-3 lg:flex lg:flex-col lg:justify-center lg:p-10">
                <p class="text-lead">{{ $home['why']['accent']['text'] }}</p>
            </x-kh.card-accent>
            @foreach ($home['why']['cards'] as $card)
                <x-kh.card :title="$card['title']" data-reveal style="--i: {{ $loop->iteration }}">{{ $card['text'] }}</x-kh.card>
            @endforeach
        </div>
    </x-kh.section>

    {{-- FAQ --}}
    @if ($faqs->isNotEmpty())
        <x-kh.section id="faq" :tone="$caseStudies->isNotEmpty() ? 'fog' : 'canvas'" eyebrow="Questions fréquentes" title="Vos questions, nos réponses">
            <x-kh.faq :items="$faqItems" class="max-w-3xl" />
        </x-kh.section>
    @endif

    <x-kh.cta-band :title="$home['cta']['title']" :text="$home['cta']['text']" :primary-label="$home['cta']['primary']" :primary-href="url('/diagnostic?entry=home')" :whatsapp="WhatsAppLink::for('home')" />
</x-layouts.public>
