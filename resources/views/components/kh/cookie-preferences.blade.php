{{-- Page « Cookies » : le visiteur revoit ou retire son choix à tout moment (PRV-01). --}}
@php
    $consent = app(\App\Domain\Platform\Analytics\AudienceConsent::class);
    $state = ! $consent->decided() ? 'Aucun choix enregistré : la mesure d’audience est désactivée.'
        : ($consent->granted() ? 'Mesure d’audience acceptée.' : 'Mesure d’audience refusée.');
@endphp
<section id="vos-choix" aria-labelledby="kh-cookie-prefs" class="rounded-3xl bg-mist p-6 lg:p-8">
    <h2 id="kh-cookie-prefs" class="text-h3 text-ink">Vos choix</h2>
    @if (session('kh.consent_saved'))
        <p role="status" class="mt-3 flex items-center gap-2 text-small font-medium text-success-600">
            <x-kh.icon name="check" :size="18" /> {{ session('kh.consent_saved') }}
        </p>
    @endif
    <p class="mt-3 text-ink">État actuel : <strong>{{ $state }}</strong></p>
    <p class="mt-2 text-small text-slate">Les cookies nécessaires (session, sécurité des formulaires, Démo Lab, mémorisation de ce choix) ne peuvent pas être désactivés : sans eux, le site ne fonctionne pas.</p>
    <form method="POST" action="{{ route('consent.update') }}" class="mt-5 flex flex-wrap gap-3">
        @csrf
        <input type="hidden" name="return" value="{{ route('legal.cookies', absolute: false) }}#vos-choix">
        <x-kh.button type="submit" name="analytics" value="refuse" variant="ghost" size="sm" :arrow="false">Refuser la mesure d’audience</x-kh.button>
        <x-kh.button type="submit" name="analytics" value="accept" variant="ghost" size="sm" :arrow="false">Accepter la mesure d’audience</x-kh.button>
    </form>
</section>
