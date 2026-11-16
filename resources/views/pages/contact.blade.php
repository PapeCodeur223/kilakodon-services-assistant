@php
    use App\Domain\Content\Support\ContentPreview;
    use App\Domain\Platform\Support\WhatsAppLink;
    use App\View\Support\StructuredData;

    $settings = app(\App\Domain\Platform\Services\Settings::class);
    $crumbs = [['label' => 'Accueil', 'url' => route('home')], ['label' => 'Contact', 'url' => route('contact')]];
    $hours = $settings->get('contact.hours');
    $showHours = filled($hours) && ContentPreview::shows($hours);
    $privacyVisible = app(\App\Domain\Content\Services\ContentFinder::class)->query(\App\Domain\Content\Models\Page::class)->where('slug', 'confidentialite')->exists();
    $primary = (string) $settings->get('contact.whatsapp_primary');
    $secondary = (string) $settings->get('contact.whatsapp_secondary');
    $email = (string) $settings->get('contact.email');

    // Canaux directs, du plus rapide au plus formel (coordonnées issues des paramètres).
    $channels = array_values(array_filter([
        ['icon' => 'brand:whatsapp', 'label' => 'WhatsApp', 'value' => WhatsAppLink::display($primary), 'href' => WhatsAppLink::for('contact'), 'external' => true, 'highlight' => true],
        ['icon' => 'phone-call', 'label' => 'Appel', 'value' => WhatsAppLink::display($secondary ?: $primary), 'href' => 'tel:'.($secondary ?: $primary), 'external' => false, 'highlight' => false],
        filled($email) ? ['icon' => 'mail', 'label' => 'E-mail', 'value' => $email, 'href' => 'mailto:'.$email, 'external' => false, 'highlight' => false] : null,
        ['icon' => 'map-pin', 'label' => 'Où nous trouver', 'value' => (string) $settings->get('contact.city'), 'href' => null, 'external' => false, 'highlight' => false],
    ]));

    $nextSteps = [
        ['title' => 'Nous lisons votre message', 'text' => 'Un membre de l’équipe prend connaissance de votre demande.'],
        ['title' => 'Nous revenons vers vous', 'text' => 'Par WhatsApp, téléphone ou e-mail'.($showHours ? ', '.\Illuminate\Support\Str::lcfirst((string) $hours) : '').'.'],
        ['title' => 'Nous préparons la suite', 'text' => 'Un échange, une démonstration ou le diagnostic, selon votre besoin.'],
    ];
@endphp
<x-layouts.public
    title="Contact"
    description="Écrivez-nous ou discutez sur WhatsApp avec l'équipe Kilakodon Services à Bamako : un projet de logiciel, une démonstration ou une question sur un outil existant."
    :schemas="[StructuredData::organization(), StructuredData::breadcrumbs($crumbs)]"
>
    <x-kh.page-hero eyebrow="Contact" title="Parlons de *votre projet*" :breadcrumbs="$crumbs"
        lead="Un message court suffit : nous revenons vers vous pour comprendre votre besoin." />

    {{-- Canaux directs : carte flottante qui chevauche l'en-tête, comme sur la page Méthode. --}}
    <section aria-label="Nous joindre directement" class="relative z-10 -mt-12 lg:-mt-16">
        <div class="kh-container">
            <ul class="grid grid-cols-1 gap-3 rounded-card bg-white p-4 shadow-pop sm:grid-cols-2 lg:grid-cols-4 lg:p-5" data-reveal="zoom">
                @foreach ($channels as $channel)
                    <li>
                        @php $tag = $channel['href'] ? 'a' : 'div'; @endphp
                        <{{ $tag }} @if ($channel['href']) href="{{ $channel['href'] }}" @if ($channel['external']) target="_blank" rel="noopener" @endif @endif
                            @class([
                                'group flex h-full items-center gap-4 rounded-input p-4 transition-colors',
                                'bg-kh-blue-700 text-white hover:bg-kh-blue-900' => $channel['highlight'],
                                'hover:bg-kh-blue-50' => ! $channel['highlight'] && $channel['href'],
                                'focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-kh-blue-500' => $channel['href'],
                            ])>
                            <span @class([
                                'flex size-12 shrink-0 items-center justify-center rounded-2xl transition-transform duration-200 group-hover:scale-110',
                                'bg-white/15 text-white' => $channel['highlight'],
                                'bg-kh-blue-50 text-kh-blue-700' => ! $channel['highlight'],
                            ])><x-kh.icon :name="$channel['icon']" :size="22" /></span>
                            <span class="min-w-0">
                                <span @class(['block text-tag font-semibold uppercase', 'text-kh-blue-200' => $channel['highlight'], 'text-slate' => ! $channel['highlight']])>{{ $channel['label'] }}</span>
                                <span @class(['mt-0.5 block font-semibold [overflow-wrap:anywhere]', 'text-white' => $channel['highlight'], 'text-ink' => ! $channel['highlight']])>{{ $channel['value'] }}</span>
                            </span>
                        </{{ $tag }}>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="kh-section bg-canvas" aria-labelledby="formulaire-title">
        <div class="kh-container grid items-start gap-10 lg:grid-cols-[1.5fr_1fr] lg:gap-12">
            <div class="rounded-card border border-line bg-white p-6 shadow-pop sm:p-10" data-reveal="left">
                <div class="mb-8 flex items-start gap-4">
                    <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-2xl bg-kh-blue-50 text-kh-blue-700"><x-kh.icon name="send" :size="24" /></span>
                    <div>
                        <h2 id="formulaire-title" class="font-display text-h3 font-extrabold text-ink">Écrivez-nous</h2>
                        <p class="mt-1 text-small text-slate">Réponse par l’équipe Kilakodon, à Bamako. Les champs marqués <span class="text-kh-red-600" aria-hidden="true">*</span><span class="sr-only">d’un astérisque</span> sont obligatoires.</p>
                    </div>
                </div>

                @if (session('contact.success'))
                    <div class="mb-8 flex items-start gap-3 rounded-input bg-success-50 p-5 text-success-600" role="status">
                        <x-kh.icon name="circle-check" :size="22" class="mt-0.5" />
                        <p>{{ session('contact.success') }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-8 flex items-start gap-3 rounded-input bg-kh-red-50 p-5 text-kh-red-600" role="alert">
                        <x-kh.icon name="circle-alert" :size="22" class="mt-0.5" />
                        <p>Certains champs sont à corriger ci-dessous.</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.store') }}" class="grid gap-6" novalidate>
                    @csrf
                    <x-honeypot />
                    <div class="grid gap-6 sm:grid-cols-2">
                        <x-kh.input name="name" label="Votre nom" required autocomplete="name" />
                        <x-kh.input name="phone" label="Téléphone / WhatsApp" type="tel" required autocomplete="tel" inputmode="tel" hint="Exemple : 74 66 64 89 ou +223 74 66 64 89" />
                    </div>
                    <div class="grid gap-6 sm:grid-cols-2">
                        <x-kh.input name="email" label="E-mail" type="email" autocomplete="email" />
                        <x-kh.select name="subject" label="Sujet" :options="$subjects" required />
                    </div>
                    <x-kh.textarea name="message" label="Votre message" required rows="6" hint="Votre activité, ce que vous cherchez à améliorer. Quelques lignes suffisent." />

                    <fieldset class="grid gap-4 rounded-input bg-mist p-5">
                        <legend class="sr-only">Consentements</legend>
                        <x-kh.checkbox name="consent_processing" required>{{ $consentTexts['processing'] ?? '' }}</x-kh.checkbox>
                        <x-kh.checkbox name="consent_marketing">{{ $consentTexts['marketing'] ?? '' }}</x-kh.checkbox>
                        @if ($privacyVisible)
                            <p class="ps-8 text-small text-slate">Voir notre <a href="{{ route('legal.privacy') }}" class="text-kh-blue-700 underline">politique de confidentialité</a>.</p>
                        @endif
                    </fieldset>

                    <div class="flex flex-wrap items-center gap-4">
                        <x-kh.button type="submit">Envoyer mon message</x-kh.button>
                        <span class="inline-flex items-center gap-2 text-small text-slate"><x-kh.icon name="shield-check" :size="18" class="text-kh-blue-700" /> Vos coordonnées servent uniquement à vous répondre.</span>
                    </div>
                </form>
            </div>

            <aside class="space-y-6 lg:sticky lg:top-28" aria-label="Après votre message">
                <x-kh.next-steps title="Ce qui se passe *ensuite*" :steps="$nextSteps" data-reveal="right" />

                <x-kh.card-accent tone="blue" title="Le plus rapide : *WhatsApp*" data-reveal="right" style="--i: 1">
                    <p>Écrivez-nous directement, un conseiller vous répond.</p>
                    <div class="mt-5"><x-kh.button variant="whatsapp" :href="WhatsAppLink::for('contact')" /></div>
                </x-kh.card-accent>
            </aside>
        </div>
    </section>

    {{-- Autres façons d'avancer, sans formulaire. --}}
    <section class="kh-section bg-fog" aria-labelledby="autres-title">
        <div class="kh-container">
            <header class="mx-auto mb-12 max-w-2xl text-center">
                <x-kh.eyebrow class="mb-5" data-reveal>Autres façons d’avancer</x-kh.eyebrow>
                <h2 id="autres-title" class="text-h2 text-ink" data-reveal style="--i: 1">{{ \App\View\Support\Accent::html('Pas encore prêt à écrire ? *Commencez ici*') }}</h2>
            </header>
            <ul class="grid gap-5 md:grid-cols-3">
                <li data-reveal style="--i: 0">
                    <x-kh.card :href="route('diagnostic.start', ['entry' => 'contact'])" icon="clipboard-check" title="Faire le diagnostic gratuit" class="h-full">
                        En 3 minutes, vos priorités et les modules adaptés, sans engagement.
                        <x-slot:footer><span class="font-medium text-kh-blue-700">Commencer <span class="kh-arrow" aria-hidden="true">→</span></span></x-slot:footer>
                    </x-kh.card>
                </li>
                <li data-reveal style="--i: 1">
                    <x-kh.card :href="route('appointment')" icon="calendar-check" title="Demander un rendez-vous" class="h-full">
                        Proposez vos créneaux : appel, visioconférence ou rencontre à Bamako.
                        <x-slot:footer><span class="font-medium text-kh-blue-700">Proposer un créneau <span class="kh-arrow" aria-hidden="true">→</span></span></x-slot:footer>
                    </x-kh.card>
                </li>
                <li data-reveal style="--i: 2">
                    <x-kh.card :href="route('pages.method')" icon="compass" title="Découvrir notre méthode" class="h-full">
                        Des étapes claires, un livrable validé par vous à chaque étape.
                        <x-slot:footer><span class="font-medium text-kh-blue-700">Voir la méthode <span class="kh-arrow" aria-hidden="true">→</span></span></x-slot:footer>
                    </x-kh.card>
                </li>
            </ul>
        </div>
    </section>
</x-layouts.public>
