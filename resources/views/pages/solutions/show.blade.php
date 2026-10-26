@php
    use App\Domain\Content\Support\ContentPreview;
    use App\Domain\Platform\Support\WhatsAppLink;
    use App\View\Support\StructuredData;

    $crumbs = [
        ['label' => 'Accueil', 'url' => route('home')],
        ['label' => 'Solutions', 'url' => route('solutions.index')],
        ['label' => $service->title, 'url' => $service->publicUrl()],
    ];
    $faq = $service->faq ?? [];
    $visible = fn ($value): bool => filled($value) && ContentPreview::shows($value);
    $diagnosticUrl = url('/diagnostic?'.http_build_query(array_filter(['entry' => 'solution:'.$service->slug, 'secteur' => $sectorCode])));
    $whatsapp = WhatsAppLink::forSolution($service->title);
    // Étapes génériques (CONTENT §2, méthode) si la solution n'a pas de process spécifique.
    $process = $service->process ?: (require resource_path('content/home.php'))['method']['steps'];
@endphp
<x-layouts.public
    :title="$service->seo_title ?: $service->title"
    :description="$service->seo_description ?: $service->tagline"
    :schemas="[StructuredData::service($service), StructuredData::faqPage($faq), StructuredData::breadcrumbs($crumbs)]"
>
    {{-- HERO 2 colonnes : texte + artefact de la solution --}}
    <section class="relative overflow-hidden pt-8 pb-16 lg:pt-12 lg:pb-24">
        <x-kh.halftone :size="420" class="absolute -right-32 -top-10 hidden md:block" animate />
        <div class="kh-container relative">
            <x-kh.breadcrumb :items="$crumbs" class="mb-10" />
            <div class="grid items-center gap-12 lg:grid-cols-[1.3fr_1fr]">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-kh.eyebrow data-reveal>Solution</x-kh.eyebrow>
                        <x-kh.status-marker :status="$service->status" />
                    </div>
                    <h1 class="mt-4 text-h1 text-ink" data-reveal style="--i: 1">{{ $service->title }}</h1>
                    <p class="kh-prose mt-5 text-lead text-slate" data-reveal style="--i: 2">{{ $service->tagline }}</p>
                    <div class="mt-8 flex flex-wrap gap-3" data-reveal style="--i: 3">
                        <x-kh.button :href="$diagnosticUrl">Faire mon diagnostic gratuit</x-kh.button>
                        <x-kh.button variant="whatsapp" :href="$whatsapp" />
                    </div>
                </div>
                <x-kh.artifact :label="$service->title" :demo="false" float :i="0" class="lg:max-w-sm lg:justify-self-end">
                    <span class="inline-flex size-12 items-center justify-center rounded-full bg-kh-blue-100 text-kh-blue-700"><x-kh.icon :name="$service->icon" :size="24" /></span>
                    <ul class="mt-4 space-y-2">
                        @foreach (array_slice($service->benefits ?? [], 0, 3) as $benefit)
                            @if ($visible($benefit))
                                <li class="flex items-start gap-2"><x-kh.icon name="check" :size="18" class="mt-0.5 text-success-600" />{{ $benefit['title'] }}</li>
                            @endif
                        @endforeach
                    </ul>
                </x-kh.artifact>
            </div>
        </div>
    </section>

    <x-kh.section id="probleme" tone="fog" eyebrow="Le problème" :title="null">
        <div class="grid gap-10 lg:grid-cols-2">
            <div data-reveal>
                <h2 class="text-h2 text-ink">Ce qui vous freine aujourd'hui</h2>
                <x-kh.markdown :content="$service->problem" class="mt-5 text-lead text-slate" />
            </div>
            <div data-reveal style="--i: 1">
                <h2 class="text-h2 text-ink">Pour qui ?</h2>
                <x-kh.markdown :content="$service->audience" class="mt-5 text-lead text-slate" />
            </div>
        </div>
    </x-kh.section>

    <x-kh.section id="ce-que-vous-obtenez" title="Ce que vous *obtenez*">
        <div class="grid gap-5 md:grid-cols-3">
            @foreach ($service->benefits ?? [] as $benefit)
                @if ($visible($benefit))
                    <x-kh.card-accent tone="blue" data-reveal style="--i: {{ $loop->index }}">
                        <h3 class="text-h3 font-medium">{{ $benefit['title'] }}</h3>
                        @if (filled($benefit['text'] ?? null))<p class="mt-2">{{ $benefit['text'] }}</p>@endif
                    </x-kh.card-accent>
                @endif
            @endforeach
        </div>
        <h3 class="mt-14 text-h3 font-medium text-ink">Fonctionnalités</h3>
        <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($service->features ?? [] as $feature)
                @if ($visible($feature))
                    <li class="flex items-start gap-3 rounded-input bg-mist p-4" data-reveal style="--i: {{ min($loop->index, 6) }}">
                        <x-kh.icon name="circle-check" :size="20" class="mt-0.5 text-kh-blue-700" />
                        <span>{{ $feature['title'] }}@if (filled($feature['text'] ?? null))<span class="block text-small text-slate">{{ $feature['text'] }}</span>@endif</span>
                    </li>
                @endif
            @endforeach
        </ul>
    </x-kh.section>

    <x-kh.section id="comment-on-travaille" tone="fog" eyebrow="Méthode" title="Comment on travaille">
        <ol class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
            @foreach ($process as $step)
                <li data-reveal style="--i: {{ $loop->index }}">
                    <span class="inline-flex size-10 items-center justify-center rounded-full bg-kh-blue-700 font-semibold text-white">{{ $loop->iteration }}</span>
                    <h3 class="mt-4 text-h3 font-medium text-ink">{{ $step['title'] }}</h3>
                    @if (filled($step['text'] ?? null))<p class="mt-2 text-slate">{{ $step['text'] }}</p>@endif
                </li>
            @endforeach
        </ol>
    </x-kh.section>

    <x-kh.section id="details">
        <div class="grid gap-5 lg:grid-cols-2">
            @if ($visible($service->limits))
                <x-kh.card-accent tone="blue" title="Ce que ce n'est pas" data-reveal>
                    <x-kh.markdown :content="$service->limits" />
                </x-kh.card-accent>
            @endif
            <x-kh.card title="Délai et prix" data-reveal style="--i: 1">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-small font-medium text-ink">Délai indicatif</dt>
                        <dd class="mt-1">{{ $visible($service->duration_hint) ? $service->duration_hint : 'Précisé après le diagnostic' }}</dd>
                    </div>
                    <div>
                        <dt class="text-small font-medium text-ink">Prix</dt>
                        <dd class="mt-1">{{ $service->priceLabel() }}</dd>
                    </div>
                </dl>
                <p class="mt-4 text-small">Le délai ferme et le prix figurent dans le devis, après notre échange.</p>
            </x-kh.card>
        </div>
    </x-kh.section>

    @php $visibleFaq = array_values(array_filter($faq, $visible)); @endphp
    @if ($visibleFaq !== [])
        <x-kh.section id="faq" tone="fog" eyebrow="Questions fréquentes" :title="'Vos questions sur '.$service->title">
            <x-kh.faq :items="$visibleFaq" class="max-w-3xl" />
        </x-kh.section>
    @endif

    @if ($sectors->isNotEmpty() || $others->isNotEmpty())
        <x-kh.section id="voir-aussi" title="À voir aussi">
            @if ($sectors->isNotEmpty())
                <p class="text-small font-medium text-ink">Secteurs concernés</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($sectors as $sector)
                        <a href="{{ $sector->publicUrl() }}" class="inline-flex min-h-11 items-center gap-2 rounded-full bg-mist px-4 hover:bg-kh-blue-50">
                            <x-kh.icon :name="$sector->icon ?? 'store'" :size="18" class="text-kh-blue-700" />{{ $sector->name }}
                        </a>
                    @endforeach
                </div>
            @endif
            @if ($others->isNotEmpty())
                <div @class(['grid gap-5 md:grid-cols-3', 'mt-10' => $sectors->isNotEmpty()])>
                    @foreach ($others as $other)
                        <x-kh.card :href="$other->publicUrl()" :icon="$other->icon" :title="$other->title">{{ $other->tagline }}</x-kh.card>
                    @endforeach
                </div>
            @endif
        </x-kh.section>
    @endif

    <x-kh.cta-band
        title="Un diagnostic *pré-rempli* pour cette solution"
        text="Répondez à quelques questions : vous saurez si cette solution correspond à votre besoin, et par où commencer."
        :primary-href="$diagnosticUrl"
        :whatsapp="$whatsapp"
    />
</x-layouts.public>
