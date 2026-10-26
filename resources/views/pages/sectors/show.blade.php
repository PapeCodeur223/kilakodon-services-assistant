@php
    use App\Domain\Platform\Support\WhatsAppLink;
    use App\View\Support\Accent;
    use App\View\Support\StructuredData;

    $crumbs = [
        ['label' => 'Accueil', 'url' => route('home')],
        ['label' => $sector->name, 'url' => $sector->publicUrl()],
    ];
    $diagnosticUrl = url('/diagnostic?'.http_build_query(['secteur' => $sector->code, 'entry' => 'sector:'.$sector->code]));
    $demos = [
        'boutique' => ['label' => 'Démo Boutique (interactive)', 'url' => url('/demo-lab/boutique')],
        'gestion-scolaire' => ['label' => 'Démo guidée — gestion scolaire', 'url' => url('/demo-lab/gestion-scolaire')],
        'boulangerie' => ['label' => 'Démo guidée — boulangerie', 'url' => url('/demo-lab/boulangerie')],
    ];
    $demo = $demos[$sector->demo_slug] ?? null;
@endphp
<x-layouts.public
    :title="$sector->seo_title ?: 'Logiciel de gestion — '.$sector->name"
    :description="$sector->seo_description ?: \Illuminate\Support\Str::limit($sector->intro, 158)"
    :schemas="[StructuredData::breadcrumbs($crumbs)]"
>
    <x-kh.page-hero :eyebrow="$sector->name" :title="$sector->headline" :lead="$sector->intro" :breadcrumbs="$crumbs" :status="$sector->status">
        <div class="flex flex-wrap gap-3">
            <x-kh.button :href="$diagnosticUrl">Diagnostic {{ mb_strtolower($sector->name) }}</x-kh.button>
            <x-kh.button variant="whatsapp" :href="WhatsAppLink::for('sector:'.$sector->code)" />
        </div>
    </x-kh.page-hero>

    <x-kh.section id="defis" tone="fog">
        <div class="grid gap-12 lg:grid-cols-2">
            <div>
                <h2 class="text-h2 text-ink" data-reveal>Vos défis</h2>
                <ul class="mt-8 space-y-4">
                    @foreach ($sector->pain_points as $pain)
                        <li class="flex items-start gap-3 rounded-input bg-white p-4" data-reveal style="--i: {{ $loop->index }}">
                            <x-kh.icon name="circle-alert" :size="20" class="mt-0.5 text-kh-red-600" />
                            <span>{{ $pain['title'] }}@if (filled($pain['text'] ?? null))<span class="block text-small text-slate">{{ $pain['text'] }}</span>@endif</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h2 class="text-h2 text-ink" data-reveal>Ce que ça <em>change</em></h2>
                <ul class="mt-8 space-y-4">
                    @foreach ($sector->outcomes as $outcome)
                        <li class="flex items-start gap-3 rounded-input bg-white p-4" data-reveal style="--i: {{ $loop->index }}">
                            <x-kh.icon name="circle-check" :size="20" class="mt-0.5 text-success-600" />
                            <span>{{ $outcome['title'] }}@if (filled($outcome['text'] ?? null))<span class="block text-small text-slate">{{ $outcome['text'] }}</span>@endif</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </x-kh.section>

    @if ($services->isNotEmpty())
        <x-kh.section id="solutions-recommandees" eyebrow="Solutions" title="Les solutions recommandées">
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $service)
                    <x-kh.card :href="$service->publicUrl()" :icon="$service->icon" :title="$service->title" data-reveal style="--i: {{ $loop->index }}">
                        {{ $service->tagline }}
                        <x-slot:footer><x-kh.status-marker :status="$service->status" /></x-slot:footer>
                    </x-kh.card>
                @endforeach
            </div>
        </x-kh.section>
    @endif

    @if ($demo)
        <x-kh.section id="demo" tone="fog" eyebrow="Démo Lab" title="Voyez-le *en action*">
            <x-kh.card :href="$demo['url']" icon="sparkles" :title="$demo['label']" class="max-w-xl" data-reveal>
                Des données fictives, un vrai logiciel : explorez à votre rythme.
            </x-kh.card>
        </x-kh.section>
    @endif

    @if ($caseStudy)
        <x-kh.section id="realisation" eyebrow="Réalisation" :title="$caseStudy->title">
            <x-kh.card :href="$caseStudy->publicUrl()" class="max-w-xl" data-reveal>{{ $caseStudy->clientLabel() }}</x-kh.card>
        </x-kh.section>
    @endif

    <x-kh.cta-band
        :title="'Diagnostic '.mb_strtolower($sector->name).' : *3 minutes*'"
        text="Le diagnostic est déjà adapté à votre secteur. Vous repartez avec vos priorités et les modules recommandés."
        :primary-label="'Diagnostic '.mb_strtolower($sector->name)"
        :primary-href="$diagnosticUrl"
        :whatsapp="WhatsAppLink::for('sector:'.$sector->code)"
    />
</x-layouts.public>
