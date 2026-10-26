@php
    use App\Domain\Platform\Support\WhatsAppLink;

    $nav = config('kh_navigation');
    $settings = app(\App\Domain\Platform\Services\Settings::class);
    $phones = array_filter([$settings->get('contact.whatsapp_primary'), $settings->get('contact.whatsapp_secondary')]);
    $email = $settings->get('contact.email');
    $columns = [
        'Solutions' => collect($nav['solutions'])->map(fn ($s) => ['title' => $s['title'], 'path' => '/solutions/'.$s['slug']]),
        'Secteurs' => collect($nav['sectors'])->map(fn ($s) => ['title' => $s['title'], 'path' => '/secteurs/'.$s['slug']]),
        'Entreprise' => $nav['company'],
        'Légal' => $nav['legal'],
    ];
@endphp
{{-- Pied de page — UX §4.2. Aucun lien vers « # » : un réseau sans URL réelle n'est pas affiché. --}}
<footer class="relative overflow-hidden bg-kh-blue-950 text-kh-blue-200">
    <x-kh.halftone :size="360" tone="blue-900" class="absolute -right-24 -bottom-28 hidden sm:block" />

    <div class="kh-container relative py-16 lg:py-20">
        <div class="grid gap-12 lg:grid-cols-[1.2fr_2fr]">
            <div>
                <p class="inline-flex rounded-2xl bg-white px-4 py-3">
                    <x-kh.logo />
                </p>
                <p class="mt-6 max-w-xs text-small">Logiciels de gestion et solutions numériques sur mesure — Bamako, Mali</p>

                <address class="mt-8 space-y-3 text-small not-italic">
                    <p class="flex items-start gap-3"><x-kh.icon name="map-pin" :size="18" class="mt-0.5" />{{ $settings->get('contact.city') }}</p>
                    @foreach ($phones as $phone)
                        <p class="flex items-start gap-3">
                            <x-kh.icon name="phone" :size="18" class="mt-0.5" />
                            <a class="hover:text-white" href="tel:{{ $phone }}">{{ WhatsAppLink::display($phone) }}</a>
                        </p>
                    @endforeach
                    @if ($email)
                        <p class="flex items-start gap-3">
                            <x-kh.icon name="mail" :size="18" class="mt-0.5" />
                            <a class="hover:text-white" href="mailto:{{ $email }}">{{ $email }}</a>
                        </p>
                    @endif
                    <p class="flex items-start gap-3"><x-kh.icon name="clock" :size="18" class="mt-0.5" />{{ $settings->get('contact.hours') }}</p>
                </address>

                <div class="mt-8">
                    <x-kh.button variant="whatsapp-inverse" :href="WhatsAppLink::for('footer')" />
                </div>
            </div>

            <nav aria-label="Plan du site" class="grid grid-cols-2 gap-10 sm:grid-cols-4">
                @foreach ($columns as $heading => $links)
                    <div>
                        <h2 class="font-sans text-small font-semibold text-white">{{ $heading }}</h2>
                        <ul class="mt-4 space-y-2.5 text-small">
                            @foreach ($links as $link)
                                <li><a class="hover:text-white" href="{{ url($link['path']) }}">{{ $link['title'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>
        </div>

        <div class="mt-14 border-t border-kh-blue-900 pt-6 text-small">
            <p>© {{ now(config('kh.display_timezone'))->year }} Kilakodon Services</p>
        </div>
    </div>
</footer>
