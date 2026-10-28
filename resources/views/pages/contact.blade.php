@php
    use App\Domain\Content\Support\ContentPreview;
    use App\Domain\Platform\Support\WhatsAppLink;
    use App\View\Support\StructuredData;

    $settings = app(\App\Domain\Platform\Services\Settings::class);
    $crumbs = [['label' => 'Accueil', 'url' => route('home')], ['label' => 'Contact', 'url' => route('contact')]];
    $hours = $settings->get('contact.hours');
    $privacyVisible = app(\App\Domain\Content\Services\ContentFinder::class)->query(\App\Domain\Content\Models\Page::class)->where('slug', 'confidentialite')->exists();
@endphp
<x-layouts.public
    title="Contact"
    description="Écrivez-nous ou discutez sur WhatsApp avec l'équipe Kilakodon Services à Bamako : un projet de logiciel, une démonstration ou une question sur un outil existant."
    :schemas="[StructuredData::organization(), StructuredData::breadcrumbs($crumbs)]"
>
    <x-kh.page-hero eyebrow="Contact" title="Parlons de *votre projet*" :breadcrumbs="$crumbs"
        lead="Un message court suffit : nous revenons vers vous pour comprendre votre besoin." />

    <section class="pb-20 lg:pb-28">
        <div class="kh-container grid gap-12 lg:grid-cols-[1.4fr_1fr]">
            <div>
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
                    <x-kh.input name="email" label="E-mail" type="email" autocomplete="email" />
                    <x-kh.select name="subject" label="Sujet" :options="$subjects" required />
                    <x-kh.textarea name="message" label="Votre message" required rows="6" hint="Votre activité, ce que vous cherchez à améliorer. Quelques lignes suffisent." />

                    <fieldset class="grid gap-4 rounded-input bg-mist p-5">
                        <legend class="sr-only">Consentements</legend>
                        <x-kh.checkbox name="consent_processing" required>{{ $consentTexts['processing'] ?? '' }}</x-kh.checkbox>
                        <x-kh.checkbox name="consent_marketing">{{ $consentTexts['marketing'] ?? '' }}</x-kh.checkbox>
                        @if ($privacyVisible)
                            <p class="ps-8 text-small text-slate">Voir notre <a href="{{ route('legal.privacy') }}" class="text-kh-blue-700 underline">politique de confidentialité</a>.</p>
                        @endif
                    </fieldset>

                    <div>
                        <x-kh.button type="submit">Envoyer mon message</x-kh.button>
                    </div>
                </form>
            </div>

            <aside class="space-y-5">
                <x-kh.card-accent tone="blue" title="Le plus rapide : *WhatsApp*">
                    <p>Écrivez-nous directement, un conseiller vous répond.</p>
                    <div class="mt-5"><x-kh.button variant="whatsapp" :href="WhatsAppLink::for('contact')" /></div>
                </x-kh.card-accent>
                <x-kh.card title="Coordonnées">
                    <address class="space-y-3 not-italic">
                        <p class="flex items-start gap-3"><x-kh.icon name="map-pin" :size="18" class="mt-1" />{{ $settings->get('contact.city') }}</p>
                        @foreach (array_filter([$settings->get('contact.whatsapp_primary'), $settings->get('contact.whatsapp_secondary')]) as $phone)
                            <p class="flex items-start gap-3"><x-kh.icon name="phone" :size="18" class="mt-1" /><a href="tel:{{ $phone }}" class="text-ink hover:text-kh-blue-700">{{ WhatsAppLink::display($phone) }}</a></p>
                        @endforeach
                        <p class="flex items-start gap-3"><x-kh.icon name="mail" :size="18" class="mt-1" /><a href="mailto:{{ $settings->get('contact.email') }}" class="text-ink hover:text-kh-blue-700">{{ $settings->get('contact.email') }}</a></p>
                        @if (filled($hours) && ContentPreview::shows($hours))
                            <p class="flex items-start gap-3"><x-kh.icon name="clock" :size="18" class="mt-1" />{{ $hours }}</p>
                        @endif
                    </address>
                </x-kh.card>
            </aside>
        </div>
    </section>
</x-layouts.public>
