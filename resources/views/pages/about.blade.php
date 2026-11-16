@php
    use App\Domain\Platform\Services\Settings;
    use App\Domain\Platform\Support\WhatsAppLink;
    use App\View\Support\AboutContent;
    use App\View\Support\Accent;
    use App\View\Support\Markdown;
    use App\View\Support\StructuredData;

    /** @var array{intro: list<string>, sections: list<array{title: string, paragraphs: list<string>, items: list<array{title: string, text: string}>, statement: string|null}>} $about */
    $crumbs = [['label' => 'Accueil', 'url' => route('home')], ['label' => 'À propos', 'url' => $page->publicUrl()]];

    $story = AboutContent::find($about['sections'], 'histoire');
    $team = AboutContent::find($about['sections'], 'equipe');
    $expertise = AboutContent::find($about['sections'], 'expertise', 'domaines');
    $mission = AboutContent::find($about['sections'], 'mission');
    $known = array_filter([$story, $team, $expertise, $mission]);
    $others = array_values(array_filter($about['sections'], fn (array $s): bool => ! in_array($s, $known, true)));

    // Chiffres clés : uniquement des informations présentes dans le texte validé ou les paramètres.
    preg_match('/fond[ée]+e?\s+en\s+\**(\d{4})/iu', implode(' ', $story['paragraphs'] ?? []), $year);
    $facts = array_values(array_filter([
        isset($year[1]) ? ['icon' => 'history', 'value' => $year[1], 'label' => 'Année de création'] : null,
        ['icon' => 'map-pin', 'value' => (string) app(Settings::class)->get('contact.city'), 'label' => 'Notre base'],
        $expertise && $expertise['items'] !== [] ? ['icon' => 'layers', 'value' => (string) count($expertise['items']), 'label' => 'Domaines d’expertise'] : null,
        ['icon' => 'handshake', 'value' => 'Sur mesure', 'label' => 'Chaque solution part de votre besoin'],
    ]));

    // Fondateur : le premier nom en gras de la section « équipe », suivi de son rôle.
    $founder = null;
    $teamParagraphs = $team['paragraphs'] ?? [];
    foreach ($teamParagraphs as $k => $paragraph) {
        if (preg_match('/\*\*([^*]+)\*\*\s*,\s*([^,]+),\s*(.+)$/u', $paragraph, $m) === 1) {
            $founder = ['name' => trim($m[1]), 'role' => trim($m[2]), 'text' => trim($m[3])];
            unset($teamParagraphs[$k]);
            break;
        }
    }
    $teamParagraphs = array_values($teamParagraphs);
    $lead = $about['intro'][0] ?? null;
    // « Notre conviction : … » : le libellé est déjà affiché au-dessus de la citation.
    $conviction = array_map(fn (string $p): string => \Illuminate\Support\Str::ucfirst((string) preg_replace('/^notre conviction\s*:\s*/iu', '', $p)), array_slice($about['intro'], 1));
@endphp
<x-layouts.public
    :title="$page->seo_title ?: 'À propos'"
    :description="$page->seo_description"
    :schemas="[StructuredData::organization(), StructuredData::breadcrumbs($crumbs)]"
>
    <x-kh.page-hero eyebrow="À propos" :title="$page->title" :lead="$lead" :breadcrumbs="$crumbs" :status="$page->status" />

    {{-- Repères : carte flottante qui chevauche l'en-tête, comme sur la page Méthode. --}}
    <section aria-label="Kilakodon Services en bref" class="relative z-10 -mt-12 lg:-mt-16">
        <div class="kh-container">
            <dl class="grid grid-cols-2 gap-3 rounded-card bg-white p-4 shadow-pop lg:grid-cols-4 lg:gap-0 lg:p-6" data-reveal="zoom">
                @foreach ($facts as $i => $fact)
                    <div @class(['flex flex-col items-center gap-2 rounded-input p-4 text-center', 'lg:border-s lg:border-line' => $i > 0])>
                        <span class="flex size-11 items-center justify-center rounded-full bg-kh-blue-50 text-kh-blue-700"><x-kh.icon :name="$fact['icon']" :size="22" /></span>
                        <dd class="font-display text-h3 font-extrabold text-ink">{{ $fact['value'] }}</dd>
                        <dt class="text-small text-slate">{{ $fact['label'] }}</dt>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    @if ($story)
        <section class="kh-section bg-canvas" aria-labelledby="histoire-title">
            <div class="kh-container grid items-start gap-12 lg:grid-cols-[1.25fr_1fr] lg:gap-20">
                <div>
                    <x-kh.eyebrow class="mb-5" data-reveal>{{ $story['title'] }}</x-kh.eyebrow>
                    <h2 id="histoire-title" class="text-h2 text-ink" data-reveal style="--i: 1">{{ Accent::html('Des besoins du terrain à des *solutions concrètes*') }}</h2>
                    <div class="mt-8 space-y-5 text-lead text-slate" data-reveal style="--i: 2">
                        @foreach ($story['paragraphs'] as $paragraph)
                            <div class="kh-markdown">{{ Markdown::html($paragraph) }}</div>
                        @endforeach
                    </div>
                </div>

                @if ($conviction !== [])
                    {{-- Notre conviction : la phrase qui résume la façon de travailler. --}}
                    <figure class="kh-dark relative overflow-hidden rounded-card p-8 sm:p-10 lg:sticky lg:top-28" data-reveal="right">
                        <div class="kh-grid-bg pointer-events-none absolute inset-0" aria-hidden="true"></div>
                        <div class="pointer-events-none absolute -right-16 -bottom-20" aria-hidden="true">
                            <x-kh.halftone :size="260" tone="blue-900" class="kh-spin opacity-70" />
                        </div>
                        <div class="relative">
                            <span class="inline-flex size-12 items-center justify-center rounded-2xl bg-white/10 text-white"><x-kh.icon name="quote" :size="24" /></span>
                            <figcaption class="mt-6 text-tag font-semibold uppercase text-kh-blue-200">Notre conviction</figcaption>
                            <blockquote class="mt-3 space-y-4 text-lead text-white">
                                @foreach ($conviction as $paragraph)
                                    <div class="kh-markdown">{{ Markdown::html($paragraph) }}</div>
                                @endforeach
                            </blockquote>
                        </div>
                    </figure>
                @endif
            </div>
        </section>
    @endif

    @if ($team)
        <section class="kh-section bg-fog" aria-labelledby="equipe-title">
            <div class="kh-container">
                <header class="mx-auto mb-14 max-w-2xl text-center">
                    <x-kh.eyebrow class="mb-5" data-reveal>{{ $team['title'] }}</x-kh.eyebrow>
                    <h2 id="equipe-title" class="text-h2 text-ink" data-reveal style="--i: 1">{{ Accent::html('Des personnes *engagées* derrière chaque projet') }}</h2>
                </header>

                <div class="grid items-stretch gap-6 lg:grid-cols-[1fr_1.4fr]">
                    @if ($founder)
                        <article class="kh-card relative overflow-hidden rounded-card border border-line bg-white p-8 text-center sm:p-10" data-reveal="left">
                            <div class="mx-auto flex size-28 items-center justify-center rounded-full bg-linear-to-br from-kh-blue-700 to-kh-blue-500 font-display text-h2 font-extrabold text-white shadow-glow ring-8 ring-kh-blue-50" aria-hidden="true">{{ AboutContent::initials($founder['name']) }}</div>
                            <h3 class="mt-6 text-h3 text-ink">{{ $founder['name'] }}</h3>
                            <p class="mt-1 text-tag font-semibold uppercase text-kh-blue-700">{{ \Illuminate\Support\Str::ucfirst($founder['role']) }}</p>
                            <p class="mt-5 text-body text-slate">{{ \Illuminate\Support\Str::ucfirst(rtrim($founder['text'], '.')) }}.</p>
                        </article>
                    @endif

                    <div class="grid gap-6">
                        @foreach ($teamParagraphs as $i => $paragraph)
                            <div class="flex items-start gap-4 rounded-card border border-line bg-white p-6 sm:p-7" data-reveal="right" style="--i: {{ $i }}">
                                <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-2xl bg-kh-blue-50 text-kh-blue-700">
                                    <x-kh.icon :name="['users-round', 'puzzle', 'heart-handshake', 'badge-check'][$i % 4]" :size="22" />
                                </span>
                                <div class="kh-markdown text-body text-ink">{{ Markdown::html($paragraph) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($expertise && $expertise['items'] !== [])
        <section class="kh-section bg-canvas" aria-labelledby="expertise-title">
            <div class="kh-container">
                <header class="mx-auto mb-14 max-w-2xl text-center">
                    <x-kh.eyebrow class="mb-5" data-reveal>{{ $expertise['title'] }}</x-kh.eyebrow>
                    <h2 id="expertise-title" class="text-h2 text-ink" data-reveal style="--i: 1">{{ Accent::html('Le numérique de votre activité, *sous un même toit*') }}</h2>
                    @foreach ($expertise['paragraphs'] as $paragraph)
                        <div class="kh-markdown mt-5 text-lead text-slate" data-reveal style="--i: 2">{{ Markdown::html($paragraph) }}</div>
                    @endforeach
                </header>

                <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($expertise['items'] as $i => $item)
                        <li data-reveal style="--i: {{ $i % 6 }}">
                            <article class="kh-card group relative h-full overflow-hidden rounded-card border border-line bg-white p-7 sm:p-8">
                                <div class="flex items-start justify-between gap-4">
                                    <span class="kh-card-icon inline-flex size-12 items-center justify-center rounded-2xl bg-kh-blue-50 text-kh-blue-700">
                                        <x-kh.icon :name="AboutContent::iconFor($item['title'])" :size="24" />
                                    </span>
                                    <span class="font-display text-[3rem] leading-none font-extrabold text-kh-blue-50 select-none" aria-hidden="true">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                </div>
                                <h3 class="mt-6 text-h3 text-ink">{{ $item['title'] }}</h3>
                                <p class="mt-3 text-body text-slate">{{ \Illuminate\Support\Str::ucfirst($item['text']) }}</p>
                            </article>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if ($mission)
        <section class="kh-section kh-dark relative overflow-hidden" aria-labelledby="mission-title">
            <div class="kh-grid-bg pointer-events-none absolute inset-0" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -left-24 -bottom-32 hidden md:block" aria-hidden="true">
                <x-kh.halftone :size="420" tone="blue-900" class="kh-spin opacity-70" />
            </div>
            <div class="kh-container relative grid gap-12 lg:grid-cols-[1fr_1.2fr] lg:gap-20">
                <div>
                    <x-kh.eyebrow tone="inverse" class="mb-5" data-reveal>{{ $mission['title'] }}</x-kh.eyebrow>
                    <h2 id="mission-title" class="text-h2 text-white" data-reveal style="--i: 1">{{ Accent::html('Rendre le numérique *accessible* à chaque organisation') }}</h2>
                    @if ($mission['statement'])
                        <p class="mt-10 border-s-4 border-kh-red-500 ps-6 font-display text-h3 font-extrabold text-white" data-reveal style="--i: 2">{{ $mission['statement'] }}</p>
                    @endif
                </div>
                <div class="space-y-5 text-lead text-kh-blue-200" data-reveal="right">
                    @foreach ($mission['paragraphs'] as $paragraph)
                        <div class="kh-markdown">{{ Markdown::html($paragraph) }}</div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @foreach ($others as $section)
        <x-kh.section :tone="$loop->odd ? 'fog' : 'canvas'" :title="$section['title']">
            <div class="kh-markdown kh-prose text-lead text-ink">
                @foreach ($section['paragraphs'] as $paragraph)
                    {{ Markdown::html($paragraph) }}
                @endforeach
                @foreach ($section['items'] as $item)
                    <p><strong>{{ $item['title'] }}</strong> : {{ $item['text'] }}</p>
                @endforeach
                @if ($section['statement'])
                    <p><strong>{{ $section['statement'] }}</strong></p>
                @endif
            </div>
        </x-kh.section>
    @endforeach

    <x-kh.cta-band
        title="Parlons de *votre projet*"
        text="Le diagnostic gratuit prépare notre premier échange : en 3 minutes, vos priorités et les modules adaptés."
        :primary-href="route('diagnostic.start', ['entry' => 'a-propos'])"
        :whatsapp="WhatsAppLink::for('a-propos', 'Bonjour Kilakodon, j\'ai lu votre présentation et j\'aimerais échanger sur un projet.')"
    />
</x-layouts.public>
