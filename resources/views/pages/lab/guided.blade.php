@php
    use App\Domain\Platform\Support\WhatsAppLink;
    use App\View\Support\StructuredData;

    $crumbs = [
        ['label' => 'Accueil', 'url' => route('home')],
        ['label' => 'Démo Lab', 'url' => route('lab.index')],
        ['label' => $demo['title'], 'url' => route('lab.guided', $slug)],
    ];
@endphp
{{-- Démo guidée (LAB-02) : « démonstration enregistrée ». Tant que Kilakodon n'a pas fourni la vidéo
     ou les captures, la page l'indique honnêtement (« bientôt disponible »). --}}
<x-layouts.public
    :title="'Démo guidée : '.mb_strtolower($demo['title'])"
    :description="$demo['text'].' Démonstration enregistrée du logiciel de Kilakodon Services, bientôt disponible.'"
    :schemas="[StructuredData::breadcrumbs($crumbs)]"
    :noindex="true"
>
    <x-kh.page-hero eyebrow="Démo guidée" :title="$demo['title']" :breadcrumbs="$crumbs" :lead="$demo['text']" />

    <section class="relative z-10 -mt-12 lg:-mt-16" aria-labelledby="video-title">
        <div class="kh-container">
            <div class="grid gap-6 rounded-card bg-white p-6 shadow-pop lg:grid-cols-[1.4fr_1fr] lg:p-10" data-reveal="zoom">
                <div class="kh-dark relative flex aspect-video items-center justify-center overflow-hidden rounded-card">
                    <div class="kh-grid-bg pointer-events-none absolute inset-0" aria-hidden="true"></div>
                    <div class="relative text-center">
                        <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/20"><x-kh.icon name="circle-play" :size="32" /></span>
                        <p class="mt-4 font-semibold text-white">Bientôt disponible</p>
                        <p class="mt-1 text-small text-kh-blue-200">La visite enregistrée est en préparation.</p>
                    </div>
                </div>
                <div>
                    <x-kh.badge icon="circle-play">Démonstration enregistrée</x-kh.badge>
                    <h2 id="video-title" class="mt-4 text-h3 text-ink">Ce que montrera la visite</h2>
                    <ul class="mt-4 space-y-3">
                        @foreach ($demo['topics'] as $topic)
                            <li class="flex items-start gap-3 text-ink"><x-kh.icon name="circle-check" :size="20" class="mt-0.5 shrink-0 text-kh-blue-700" />{{ $topic }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-6 text-small text-slate">En attendant, nous vous montrons le logiciel en direct lors d'un échange, ou vous pouvez essayer la démo interactive Boutique.</p>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <x-kh.button :href="route('appointment')">Demander une démonstration</x-kh.button>
                        <x-kh.button variant="ghost" :href="route('lab.retail')" :arrow="false">Essayer la démo Boutique</x-kh.button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <x-kh.cta-band
        title="Un logiciel pensé pour *votre activité*"
        text="Le diagnostic gratuit vous indique en 3 minutes les modules adaptés à votre établissement."
        :primary-href="route('diagnostic.start', ['secteur' => $demo['sector'], 'entry' => 'lab:'.$slug])"
        :whatsapp="WhatsAppLink::for('lab', 'Bonjour Kilakodon, j\'aimerais voir une démonstration du logiciel « '.$demo['title'].' ».')"
    />
</x-layouts.public>
