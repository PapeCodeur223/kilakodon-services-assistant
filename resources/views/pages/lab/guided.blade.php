@php
    use App\Domain\Platform\Support\WhatsAppLink;
    use App\View\Support\StructuredData;

    $crumbs = [
        ['label' => 'Accueil', 'url' => route('home')],
        ['label' => 'Démo Lab', 'url' => route('lab.index')],
        ['label' => $demo['title'], 'url' => route('lab.guided', $slug)],
    ];
    $screens = $demo['screens'] ?? [];
@endphp
{{-- Démo guidée (LAB-02) : visite commentée en captures réelles du logiciel, fournies par Kilakodon
     (noms de personnes masqués). Les chiffres visibles sont ceux de l'instance photographiée. --}}
<x-layouts.public
    :title="'Démo guidée : logiciel de '.mb_strtolower($demo['title'])"
    :description="$demo['text'].' Captures commentées du logiciel conçu par Kilakodon Services à Bamako.'"
    :schemas="[StructuredData::breadcrumbs($crumbs)]"
>
    <x-kh.page-hero eyebrow="Démo guidée" :title="$demo['title']" :breadcrumbs="$crumbs" :lead="$demo['text']" />

    <section class="relative z-10 -mt-12 lg:-mt-16" aria-labelledby="apercu-title">
        <div class="kh-container">
            <div class="rounded-card bg-white p-6 shadow-pop lg:p-10" data-reveal="zoom">
                <div class="flex flex-wrap items-center gap-3">
                    <x-kh.badge icon="circle-play">Visite en captures</x-kh.badge>
                    <x-kh.badge>Captures réelles du logiciel</x-kh.badge>
                </div>
                <h2 id="apercu-title" class="mt-4 text-h3 text-ink">Ce que vous allez voir</h2>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($demo['topics'] as $topic)
                        <li class="flex items-start gap-3 text-ink"><x-kh.icon name="circle-check" :size="20" class="mt-0.5 shrink-0 text-kh-blue-700" />{{ $topic }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    <section class="py-16 lg:py-24" aria-label="Captures commentées">
        <div class="kh-container space-y-16 lg:space-y-24">
            @foreach ($screens as $screen)
                <figure @class(['grid items-center gap-8', 'lg:grid-cols-[1.6fr_1fr]' => $loop->odd, 'lg:grid-cols-[1fr_1.6fr]' => $loop->even]) data-reveal="up">
                    <div @class(['overflow-hidden rounded-card border border-line bg-white shadow-artifact', 'lg:order-2' => $loop->even])>
                        <img src="{{ asset('images/demos/'.$screen['file'].'.webp') }}"
                            width="{{ $screen['width'] }}" height="{{ $screen['height'] }}"
                            alt="Capture d’écran — {{ $screen['title'] }}"
                            class="h-auto w-full" @if (! $loop->first) loading="lazy" @endif decoding="async">
                    </div>
                    <figcaption @class(['lg:order-1' => $loop->even])>
                        <p class="text-tag font-semibold uppercase text-kh-blue-700">Étape {{ $loop->iteration }} sur {{ count($screens) }}</p>
                        <h3 class="mt-2 text-h3 text-ink">{{ $screen['title'] }}</h3>
                        <p class="mt-3 text-slate">{{ $screen['caption'] }}</p>
                    </figcaption>
                </figure>
            @endforeach

            <div class="rounded-card bg-mist p-6 lg:p-8">
                <p class="text-ink">Chaque logiciel est adapté à votre établissement : nous vous le montrons en direct lors d’un échange, ou vous pouvez essayer la démo interactive Boutique.</p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <x-kh.button :href="route('appointment')">Demander une démonstration</x-kh.button>
                    <x-kh.button variant="ghost" :href="route('lab.retail')" :arrow="false">Essayer la démo Boutique</x-kh.button>
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
