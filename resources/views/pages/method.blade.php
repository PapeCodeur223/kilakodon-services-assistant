@php
    use App\Domain\Platform\Support\WhatsAppLink;
    use App\View\Support\StructuredData;

    /** @var array{intro: string, steps: list<array{number: int, title: string, text: string, deliverable: string|null}>, engagements: list<string>} $method */
    $crumbs = [['label' => 'Accueil', 'url' => route('home')], ['label' => 'Méthode', 'url' => $page->publicUrl()]];
    $stepIcons = ['messages-square', 'pencil-ruler', 'code-xml', 'flask-conical', 'rocket', 'life-buoy'];
    $engagementIcons = ['file-text', 'badge-check', 'book-open', 'user-round', 'shield-check', 'handshake'];
    $count = count($method['steps']);
@endphp
<x-layouts.public
    :title="$page->seo_title ?: 'Notre méthode'"
    :description="$page->seo_description"
    :schemas="[StructuredData::breadcrumbs($crumbs)]"
>
    <x-kh.page-hero eyebrow="Méthode" :title="$page->title" :lead="$method['intro'] ?: null" :breadcrumbs="$crumbs" :status="$page->status" />

    {{-- Vue d'ensemble : le parcours en un coup d'œil, chaque pastille mène à son étape. --}}
    <section aria-label="Les étapes en un coup d'œil" class="relative z-10 -mt-12 lg:-mt-16">
        <div class="kh-container">
            <ol class="relative grid grid-cols-2 gap-3 rounded-card bg-white p-4 shadow-pop sm:grid-cols-3 lg:grid-cols-6 lg:gap-0 lg:p-6" data-reveal="zoom">
                <span class="kh-method-line pointer-events-none absolute top-[3.25rem] right-[8%] left-[8%] hidden h-0.5 bg-linear-to-r from-kh-blue-700 via-kh-blue-500 to-kh-red-500 lg:block" aria-hidden="true"></span>
                @foreach ($method['steps'] as $i => $step)
                    <li class="relative">
                        <a href="#etape-{{ $step['number'] }}" class="group flex flex-col items-center gap-3 rounded-input p-3 text-center transition-colors hover:bg-kh-blue-50 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-kh-blue-500">
                            <span class="relative flex size-11 items-center justify-center rounded-full bg-kh-blue-700 font-display text-small font-extrabold text-white ring-4 ring-white transition-transform duration-200 group-hover:scale-110">{{ $step['number'] }}</span>
                            <span class="text-small font-semibold leading-snug text-ink">{{ $step['title'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Frise détaillée : une étape = un livrable que vous validez. --}}
    <section class="kh-section bg-canvas" aria-labelledby="etapes-title">
        <div class="kh-container">
            <header class="mx-auto mb-14 max-w-2xl text-center lg:mb-20">
                <x-kh.eyebrow class="mb-5" data-reveal>{{ $count }} étapes</x-kh.eyebrow>
                <h2 id="etapes-title" class="text-h2 text-ink" data-reveal style="--i: 1">{{ \App\View\Support\Accent::html('Un livrable *validé par vous* à chaque étape') }}</h2>
                <p class="mt-5 text-lead text-slate" data-reveal style="--i: 2">Vous savez toujours où en est votre projet, ce que nous attendons de vous et ce que vous recevez.</p>
            </header>

            <ol class="relative">
                {{-- Fil conducteur vertical (au centre sur grand écran). --}}
                <span class="pointer-events-none absolute top-0 bottom-0 left-6 w-0.5 bg-linear-to-b from-kh-blue-700 via-kh-blue-300 to-kh-red-400 lg:left-1/2 lg:-translate-x-1/2" aria-hidden="true"></span>

                @foreach ($method['steps'] as $i => $step)
                    @php $right = $i % 2 === 1; @endphp
                    {{-- Grand écran : cartes en quinconce qui se chevauchent pour éviter les vides en face de chaque carte. --}}
                    <li id="etape-{{ $step['number'] }}" @class([
                        'relative scroll-mt-28 pb-10 ps-20 last:pb-0 lg:ps-0 lg:pb-0',
                        'lg:-mt-36' => $i > 0,
                    ])>
                        {{-- Jalon numéroté sur le fil. --}}
                        <span class="absolute top-2 left-0 z-10 flex size-12 items-center justify-center rounded-full bg-kh-blue-700 font-display text-lead font-extrabold text-white shadow-glow ring-8 ring-canvas lg:left-1/2 lg:-translate-x-1/2" aria-hidden="true">{{ $step['number'] }}</span>

                        <article @class([
                            'kh-card group relative rounded-card border border-line bg-white p-7 sm:p-9 lg:w-[calc(50%-3.5rem)]',
                            'lg:ms-auto' => $right,
                        ]) data-reveal="{{ $right ? 'right' : 'left' }}">
                            <div class="flex items-start justify-between gap-4">
                                <span class="kh-card-icon inline-flex size-12 items-center justify-center rounded-2xl bg-kh-blue-50 text-kh-blue-700">
                                    <x-kh.icon :name="$stepIcons[$i % count($stepIcons)]" :size="24" />
                                </span>
                                <span class="font-display text-[3.5rem] leading-none font-extrabold text-kh-blue-50 select-none" aria-hidden="true">{{ str_pad((string) $step['number'], 2, '0', STR_PAD_LEFT) }}</span>
                            </div>
                            <p class="mt-6 text-tag font-semibold uppercase text-kh-blue-700">Étape {{ $step['number'] }} sur {{ $count }}</p>
                            <h3 class="mt-2 text-h3 text-ink">{{ $step['title'] }}</h3>
                            @if ($step['text'] !== '')
                                <p class="mt-3 text-body text-slate">{{ $step['text'] }}</p>
                            @endif
                            @if ($step['deliverable'])
                                <div class="mt-6 flex items-start gap-3 rounded-input bg-fog p-4">
                                    <x-kh.icon name="file-check" :size="22" class="mt-0.5 shrink-0 text-kh-blue-700" />
                                    <p class="text-small text-ink"><span class="font-semibold">Livrable :</span> {{ $step['deliverable'] }}</p>
                                </div>
                            @endif
                        </article>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    @if ($method['engagements'] !== [])
        <x-kh.section id="engagements" tone="dark" eyebrow="Nos engagements" title="Ce qui *ne change jamais*, quel que soit le projet" centered>
            <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($method['engagements'] as $i => $engagement)
                    <li data-reveal style="--i: {{ $i }}">
                        <x-kh.card tone="dark" :icon="$engagementIcons[$i % count($engagementIcons)]" :title="$engagement" class="h-full" />
                    </li>
                @endforeach
            </ul>
        </x-kh.section>
    @endif

    <x-kh.cta-band
        title="Prêt à démarrer *l'étape 1* ?"
        text="Le diagnostic gratuit prépare notre premier échange : en 3 minutes, vos priorités et les modules adaptés."
        :primary-href="route('diagnostic.start', ['entry' => 'methode'])"
        :whatsapp="WhatsAppLink::for('methode', 'Bonjour Kilakodon, j\'ai lu votre méthode et j\'aimerais démarrer un projet.')"
    />
</x-layouts.public>
