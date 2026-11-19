{{--
    Bannière cookies minimale (PRV-01, lot 6) : affichée tant que le visiteur n'a pas choisi.
    Formulaire classique (fonctionne sans JavaScript) ; « Refuser » aussi accessible qu'« Accepter ».
    Mesure désactivée par défaut jusqu'à acceptation (Q-011).
--}}
@if (! app(\App\Domain\Platform\Analytics\AudienceConsent::class)->decided())
    <section aria-labelledby="kh-cookies-title" data-cookie-banner
        class="fixed inset-x-3 bottom-3 z-50 rounded-3xl border border-line bg-white p-5 shadow-hover sm:inset-x-auto sm:left-4 sm:bottom-4 sm:max-w-md">
        <h2 id="kh-cookies-title" class="font-semibold text-ink">Mesure d’audience</h2>
        <p class="mt-2 text-small text-slate">
            Avec votre accord, nous mesurons la fréquentation du site avec notre propre outil : aucune publicité, aucune donnée transmise à des tiers.
            Les cookies nécessaires au fonctionnement (sécurité, formulaires, Démo Lab) restent actifs.
            <a href="{{ route('legal.cookies') }}" class="font-medium text-kh-blue-700 underline underline-offset-2">En savoir plus</a>
        </p>
        <form method="POST" action="{{ route('consent.update') }}" class="mt-4 flex flex-wrap gap-3">
            @csrf
            <input type="hidden" name="return" value="{{ request()->getRequestUri() }}">
            <x-kh.button type="submit" name="analytics" value="refuse" variant="ghost" size="sm" :arrow="false">Refuser</x-kh.button>
            <x-kh.button type="submit" name="analytics" value="accept" variant="ghost" size="sm" :arrow="false">Accepter</x-kh.button>
        </form>
    </section>
@endif
