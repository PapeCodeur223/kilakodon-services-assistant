@php
    use App\Domain\Platform\Support\WhatsAppLink;
    use App\View\Support\Accent;
    use App\View\Support\StructuredData;

    $faqItems = $faqs->map(fn ($faq) => ['q' => $faq->question, 'a' => $faq->answer])->all();
    $published = $services->keyBy('slug');
@endphp
<x-layouts.public :schemas="[StructuredData::organization(), StructuredData::faqPage($faqItems)]">

    {{-- ================= HERO ================= --}}
    <section class="kh-dark relative -mt-[72px] overflow-hidden pt-[72px]" aria-labelledby="hero-title">
        <div class="kh-grid-bg pointer-events-none absolute inset-0" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -right-40 top-10 hidden lg:block" aria-hidden="true" data-parallax="0.18">
            <x-kh.halftone :size="760" tone="blue-900" class="kh-spin opacity-60" animate />
        </div>

        <div class="kh-container relative grid items-center gap-14 py-16 lg:min-h-[calc(100vh-72px)] lg:grid-cols-[1.1fr_1fr] lg:py-24">
            <div>
                <x-kh.eyebrow tone="inverse" data-reveal>{{ $home['hero']['eyebrow'] }}</x-kh.eyebrow>
                <h1 id="hero-title" class="mt-7 text-display text-white">
                    @foreach ($home['hero']['title_lines'] as $line)
                        <span class="kh-hero-line" style="--l: {{ $loop->index }}"><span>{{ Accent::html($line) }}</span></span>
                    @endforeach
                </h1>
                <p class="kh-prose mt-7 text-lead text-kh-blue-200" data-reveal style="--i: 3">{{ $home['hero']['lead'] }}</p>
                <div class="mt-10 flex flex-wrap items-center gap-3" data-reveal style="--i: 4">
                    <x-kh.button size="lg" :href="url('/diagnostic?entry=home')">{{ $home['hero']['cta_primary'] }}</x-kh.button>
                    <x-kh.button variant="ghost-inverse" size="lg" :href="url('/demo-lab')">{{ $home['hero']['cta_secondary'] }}</x-kh.button>
                </div>
                <ul class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-small text-kh-blue-200" data-reveal style="--i: 5">
                    @foreach ($home['hero']['reassurance'] as $item)
                        <li class="flex items-center gap-2"><x-kh.icon name="circle-check" :size="18" class="text-success-50" />{{ $item }}</li>
                    @endforeach
                </ul>
            </div>

            {{-- Composition : tableau de bord + fragments flottants (données de démonstration). --}}
            <div class="relative lg:ps-6" data-parallax="-0.06">
                <div class="kh-float" style="--i: 0">
                    <x-kh.dashboard-mockup data-reveal="fade" />
                </div>
                <x-kh.artifact label="Alerte stock" float :i="1" class="absolute -left-4 -bottom-10 hidden w-64 sm:block lg:-left-14">
                    <p class="flex items-start gap-2">
                        <x-kh.icon name="triangle-alert" :size="18" class="mt-0.5 text-kh-red-600" />
                        <span><strong class="font-semibold">Riz 25 kg</strong> : 4 sacs restants (seuil 10)</span>
                    </p>
                </x-kh.artifact>
                <x-kh.artifact label="Brief" float :i="2" class="absolute -right-4 -top-24 hidden w-60 xl:block">
                    <p class="font-display font-extrabold">KLK-2026-0042</p>
                    <p class="mt-1 text-slate">Boutique, 3 points de vente</p>
                    <div class="mt-2 flex flex-wrap gap-1"><x-kh.badge tone="blue">Stock &amp; caisse</x-kh.badge><x-kh.badge tone="blue">Multi-sites</x-kh.badge></div>
                </x-kh.artifact>
            </div>
        </div>

        {{-- Bandeau défilant des savoir-faire --}}
        <div class="kh-marquee relative border-t border-white/10 py-6" aria-label="Nos savoir-faire">
            <ul class="kh-marquee-track gap-10 pe-10 text-small font-semibold uppercase tracking-[0.14em] text-kh-blue-300">
                @foreach ([...$home['expertise'], ...$home['expertise']] as $item)
                    <li class="flex shrink-0 items-center gap-10" @if ($loop->index >= count($home['expertise'])) aria-hidden="true" @endif>
                        {{ $item }} <span class="size-1.5 rounded-full bg-kh-red-500" aria-hidden="true"></span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ================= RÉALITÉS LOCALES ================= --}}
    <section class="relative bg-canvas py-20 lg:py-24" aria-labelledby="realites-title">
        <div class="kh-container">
            <div class="grid gap-10 lg:grid-cols-[0.8fr_2fr] lg:items-start">
                <div data-reveal="left">
                    <x-kh.eyebrow>Réalités du terrain</x-kh.eyebrow>
                    <h2 id="realites-title" class="mt-5 text-h2 text-ink">Conçu pour la façon dont on <em>travaille ici</em></h2>
                </div>
                <ul class="grid gap-4 sm:grid-cols-2">
                    @foreach ($home['realities']['items'] as $item)
                        <li data-reveal style="--i: {{ $loop->index }}">
                            <x-kh.card :icon="$item['icon']" :title="$item['title']" class="h-full">{{ $item['text'] }}</x-kh.card>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- ================= VOUS VOUS RECONNAISSEZ ? ================= --}}
    <x-kh.section id="reconnaitre" tone="fog" eyebrow="Vous vous reconnaissez ?" :title="$home['recognize']['title']">
        <div class="grid gap-5 md:grid-cols-2">
            @foreach ($home['recognize']['items'] as $item)
                @php $solution = $published->get($item['solution']); @endphp
                <x-kh.card :href="$solution?->publicUrl()" :icon="$item['icon']" data-reveal="{{ $loop->odd ? 'left' : 'right' }}" style="--i: {{ $loop->index }}">
                    <p class="font-display text-[1.35rem] font-bold leading-snug text-ink">« {{ $item['quote'] }} »</p>
                    <p class="mt-3">{{ $item['answer'] }}</p>
                    <x-slot:footer>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-kh-blue-50 px-3 py-1 text-small font-semibold text-kh-blue-700">
                            <x-kh.icon name="zap" :size="14" />{{ $item['solution_label'] }}
                        </span>
                    </x-slot:footer>
                </x-kh.card>
            @endforeach
        </div>
    </x-kh.section>

    {{-- ================= SOLUTIONS (grille « bento ») ================= --}}
    <x-kh.section id="solutions" eyebrow="Solutions" :title="$home['solutions']['title']" :lead="$home['solutions']['lead']">
        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($home['solutions']['items'] as $item)
                @php $solution = $published->get($item['slug']); @endphp
                @if ($loop->first)
                    {{-- Carte mise en avant, sombre. --}}
                    <x-kh.card tone="dark" :href="$solution?->publicUrl()" :icon="$item['icon']" :title="$item['title']" class="md:col-span-2 lg:row-span-2 lg:flex lg:flex-col lg:justify-between lg:p-10" data-reveal="zoom">
                        <p class="text-lead">{{ $item['tagline'] }}</p>
                        <ul class="mt-8 grid gap-3 sm:grid-cols-2">
                            @foreach (['Ventes, devis et commandes', 'Fichier clients et relances', 'Achats et fournisseurs', 'Rapports et droits d\'accès'] as $feature)
                                <li class="flex items-center gap-2 text-white"><x-kh.icon name="check" :size="18" class="text-kh-red-400" />{{ $feature }}</li>
                            @endforeach
                        </ul>
                        <x-slot:footer>
                            <x-kh.status-marker :status="$solution?->status" />
                        </x-slot:footer>
                    </x-kh.card>
                @else
                    <x-kh.card :href="$solution?->publicUrl()" :icon="$item['icon']" :title="$item['title']" data-reveal style="--i: {{ $loop->index }}">
                        {{ $item['tagline'] }}
                        @if ($solution)
                            <x-slot:footer>
                                <span class="font-semibold text-kh-blue-700">Découvrir <span class="kh-arrow" aria-hidden="true">→</span></span>
                                <x-kh.status-marker :status="$solution->status" />
                            </x-slot:footer>
                        @endif
                    </x-kh.card>
                @endif
            @endforeach
        </div>
    </x-kh.section>

    {{-- ================= LOGICIELS ÉPROUVÉS ================= --}}
    <x-kh.section id="logiciels" tone="dark" :eyebrow="$home['products']['eyebrow']" :title="$home['products']['title']" :lead="$home['products']['lead']">
        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ($home['products']['items'] as $product)
                <article class="kh-card kh-glass group flex flex-col rounded-card p-8 lg:p-10" data-reveal="{{ $loop->first ? 'left' : 'right' }}">
                    <div class="flex items-center gap-4">
                        <span class="kh-card-icon inline-flex size-14 items-center justify-center rounded-2xl bg-white/10 text-white">
                            <x-kh.icon :name="$product['icon']" :size="28" />
                        </span>
                        <div>
                            <p class="text-tag font-semibold uppercase text-kh-blue-300">Logiciel Kilakodon</p>
                            <h3 class="text-h3 text-white">{{ $product['name'] }}</h3>
                        </div>
                    </div>
                    <p class="mt-6 text-kh-blue-200">{{ $product['text'] }}</p>
                    <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                        @foreach ($product['features'] as $feature)
                            <li class="flex items-center gap-2 text-small text-white"><x-kh.icon name="circle-check" :size="18" class="text-kh-red-400" />{{ $feature }}</li>
                        @endforeach
                    </ul>
                    <div class="mt-8 flex flex-wrap gap-3 pt-2">
                        <x-kh.button variant="inverse" size="sm" :arrow="false" :href="url('/secteurs/'.$product['sector'])">Voir le détail</x-kh.button>
                        <x-kh.button variant="whatsapp-inverse" size="sm" :href="WhatsAppLink::for('product', $product['whatsapp'])">Démo sur WhatsApp</x-kh.button>
                    </div>
                </article>
            @endforeach
        </div>
    </x-kh.section>

    {{-- ================= DIAGNOSTIC ================= --}}
    <x-kh.section id="diagnostic" :eyebrow="$home['diagnostic']['eyebrow']" :title="$home['diagnostic']['title']">
        <div class="grid items-center gap-14 lg:grid-cols-2">
            <div>
                <ol class="relative space-y-6">
                    <span class="absolute top-6 bottom-6 left-6 w-px bg-linear-to-b from-kh-blue-200 via-kh-blue-200 to-transparent" aria-hidden="true"></span>
                    @foreach ($home['diagnostic']['steps'] as $step)
                        <li class="relative flex gap-5" data-reveal="left" style="--i: {{ $loop->index }}">
                            <span class="relative z-10 inline-flex size-12 shrink-0 items-center justify-center rounded-2xl bg-kh-blue-700 font-display text-lg font-extrabold text-white shadow-[var(--shadow-glow)]">{{ $loop->iteration }}</span>
                            <div class="pt-1">
                                <h3 class="text-h3 text-ink">{{ $step['title'] }}</h3>
                                <p class="mt-2 text-slate">{{ $step['text'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
                <div class="mt-10" data-reveal style="--i: 3">
                    <x-kh.button size="lg" :href="url('/diagnostic?entry=home')">{{ $home['diagnostic']['cta'] }}</x-kh.button>
                    <p class="mt-3 text-small text-slate">{{ $home['diagnostic']['note'] }}</p>
                </div>
            </div>
            <div class="relative" data-reveal="right">
                <div class="kh-grid-bg-light pointer-events-none absolute -inset-10" aria-hidden="true"></div>
                <x-kh.artifact label="Votre diagnostic" class="relative lg:ms-auto lg:max-w-md lg:p-7">
                    <div class="flex items-center gap-2" aria-hidden="true">
                        @for ($i = 1; $i <= 4; $i++)
                            <span @class(['h-2 flex-1 rounded-full', 'bg-kh-blue-700' => $i <= 2, 'bg-kh-blue-100' => $i > 2])></span>
                        @endfor
                    </div>
                    <p class="mt-3 font-display text-xl font-extrabold">Niveau 2 sur 4 · Outils dispersés</p>
                    <p class="mt-5 text-tag font-semibold uppercase text-slate">Ce que nous recommandons</p>
                    <ul class="mt-3 space-y-2">
                        @foreach (['Stock & caisse' => 'Vous avez signalé des écarts de stock.', 'Multi-sites' => 'Vous avez 3 points de vente.', 'Tableau de bord' => 'Vos rapports sont faits à la main.'] as $module => $why)
                            <li class="rounded-xl bg-mist px-4 py-3">
                                <p class="font-semibold">{{ $module }}</p>
                                <p class="text-small text-slate"><span class="font-semibold text-kh-blue-700">Pourquoi ?</span> {{ $why }}</p>
                            </li>
                        @endforeach
                    </ul>
                </x-kh.artifact>
            </div>
        </div>
    </x-kh.section>

    {{-- ================= DÉMO LAB ================= --}}
    <x-kh.section id="demo-lab" tone="fog" eyebrow="Démo Lab" :title="$home['lab']['title']" :lead="$home['lab']['text']">
        <div class="grid items-center gap-10 lg:grid-cols-[1fr_1.3fr]">
            <ul class="space-y-3" data-reveal="left">
                @foreach (['shopping-cart' => 'Faites une vente', 'bell' => 'Déclenchez une alerte de stock', 'receipt' => 'Imprimez une facture'] as $icon => $mission)
                    <li class="kh-card flex items-center gap-4 rounded-2xl border border-line bg-white p-4">
                        <span class="kh-card-icon inline-flex size-11 items-center justify-center rounded-xl bg-kh-blue-50 text-kh-blue-700"><x-kh.icon :name="$icon" :size="20" /></span>
                        <span class="font-semibold">Mission {{ $loop->iteration }} · {{ $mission }}</span>
                    </li>
                @endforeach
                <li class="pt-4"><x-kh.button size="lg" :href="url('/demo-lab/boutique')">{{ $home['lab']['cta'] }}</x-kh.button></li>
            </ul>
            <x-kh.artifact label="Démo Boutique — caisse" data-reveal="right" class="lg:p-7">
                <div class="grid grid-cols-3 gap-2 text-center text-small">
                    @foreach (['Riz 25 kg' => '18 000', 'Huile 5 L' => '9 500', 'Sucre 1 kg' => '800', 'Savon' => '350', 'Lait en poudre' => '2 750', 'Crédit tél.' => '1 000'] as $product => $price)
                        <span class="rounded-xl bg-mist px-2 py-4 transition-transform duration-200 hover:-translate-y-0.5"><span class="block font-semibold">{{ $product }}</span><span class="text-[0.75rem] text-slate">{{ $price }} F</span></span>
                    @endforeach
                </div>
                <div class="mt-3 flex items-center justify-between rounded-xl bg-kh-blue-700 px-4 py-3 text-white">
                    <span>Panier : 3 articles · <strong>28 300 F</strong></span>
                    <span class="font-semibold">Encaisser →</span>
                </div>
            </x-kh.artifact>
        </div>
    </x-kh.section>

    {{-- ================= MÉTHODE ================= --}}
    <x-kh.section id="methode" eyebrow="Méthode" :title="$home['method']['title']">
        <div class="relative" data-reveal="fade">
            <span class="kh-method-line absolute top-6 right-0 left-0 hidden h-0.5 bg-linear-to-r from-kh-blue-700 via-kh-blue-500 to-kh-red-500 lg:block" aria-hidden="true"></span>
            <ol class="relative grid gap-6 lg:grid-cols-4">
                @foreach ($home['method']['steps'] as $step)
                    <li data-reveal style="--i: {{ $loop->index }}">
                        <span class="relative inline-flex size-12 items-center justify-center rounded-2xl bg-kh-blue-700 font-display text-lg font-extrabold text-white ring-8 ring-white">{{ sprintf('%02d', $loop->iteration) }}</span>
                        <x-kh.card :title="$step['title']" class="mt-6 h-[calc(100%-4.5rem)]">{{ $step['text'] }}</x-kh.card>
                    </li>
                @endforeach
            </ol>
        </div>
        @if ($methodPageVisible)
            <div class="mt-12"><x-kh.link-arrow :href="route('pages.method')">{{ $home['method']['cta'] }}</x-kh.link-arrow></div>
        @endif
    </x-kh.section>

    {{-- ================= RÉALISATIONS : masquée tant qu'aucune n'est autorisée et publiée ================= --}}
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

    {{-- ================= POURQUOI KILAKODON ================= --}}
    <x-kh.section id="pourquoi" :tone="$caseStudies->isNotEmpty() ? 'canvas' : 'fog'" eyebrow="Pourquoi Kilakodon" :title="$home['why']['title']">
        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            <x-kh.card-accent tone="red" :title="$home['why']['accent']['title']" as="h3" data-reveal="zoom" class="md:col-span-2 lg:col-span-1 lg:row-span-2 lg:flex lg:flex-col lg:justify-center lg:p-10">
                <p class="text-lead">{{ $home['why']['accent']['text'] }}</p>
            </x-kh.card-accent>
            @foreach ($home['why']['cards'] as $card)
                <x-kh.card :icon="$card['icon']" :title="$card['title']" data-reveal style="--i: {{ $loop->iteration }}">{{ $card['text'] }}</x-kh.card>
            @endforeach
        </div>
    </x-kh.section>

    {{-- ================= FAQ ================= --}}
    @if ($faqs->isNotEmpty())
        <x-kh.section id="faq" :tone="$caseStudies->isNotEmpty() ? 'fog' : 'canvas'">
            <div class="grid gap-12 lg:grid-cols-[0.9fr_1.6fr]">
                <div data-reveal="left">
                    <x-kh.eyebrow>Questions fréquentes</x-kh.eyebrow>
                    <h2 class="mt-5 text-h2 text-ink">Vos questions, nos <em>réponses</em></h2>
                    <p class="mt-5 text-slate">Une autre question ? Un conseiller vous répond directement.</p>
                    <div class="mt-8"><x-kh.button variant="whatsapp" :href="WhatsAppLink::for('faq')" /></div>
                </div>
                <x-kh.faq :items="$faqItems" data-reveal="right" />
            </div>
        </x-kh.section>
    @endif

    <x-kh.cta-band :title="$home['cta']['title']" :text="$home['cta']['text']" :primary-label="$home['cta']['primary']" :primary-href="url('/diagnostic?entry=home')" :whatsapp="WhatsAppLink::for('home')" />
</x-layouts.public>
