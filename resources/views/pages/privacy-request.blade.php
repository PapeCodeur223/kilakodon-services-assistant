@php
    use App\Domain\Platform\Support\WhatsAppLink;
    use App\View\Support\StructuredData;

    $crumbs = [['label' => 'Accueil', 'url' => route('home')], ['label' => 'Gérer mes données', 'url' => route('privacy.form')]];
    $privacyVisible = app(\App\Domain\Content\Services\ContentFinder::class)->query(\App\Domain\Content\Models\Page::class)->where('slug', 'confidentialite')->exists();

    $steps = [
        ['title' => 'Vous envoyez votre demande', 'text' => 'Indiquez qui vous êtes et ce que vous souhaitez.'],
        ['title' => 'Nous vérifions votre identité', 'text' => 'Un membre de l’équipe vous recontacte sur le numéro ou l’adresse indiqués, pour s’assurer que la demande vient bien de vous.'],
        ['title' => 'Nous traitons la demande', 'text' => 'Copie de vos données, correction, suppression ou arrêt des messages : nous vous confirmons ce qui a été fait.'],
    ];
@endphp
<x-layouts.public
    title="Gérer mes données"
    description="Demandez une copie, une correction ou la suppression de vos données personnelles chez Kilakodon Services, ou ne plus être contacté. Traitement par l'équipe à Bamako."
    :noindex="true"
    :schemas="[StructuredData::breadcrumbs($crumbs)]"
>
    <x-kh.page-hero eyebrow="Vos données" title="Gérer *mes données*" :breadcrumbs="$crumbs"
        lead="Vous pouvez demander à tout moment une copie de vos données, leur correction ou leur suppression, ou ne plus recevoir de messages de notre part." />

    <section class="pb-20 lg:pb-28" aria-labelledby="formulaire-title">
        <div class="kh-container grid gap-12 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
            <div class="rounded-card bg-white p-6 shadow-pop lg:p-10">
                <h2 id="formulaire-title" class="font-display text-h3 font-extrabold text-ink">Votre demande</h2>
                <p class="mt-1 text-small text-slate">Les champs marqués <span class="text-kh-red-600" aria-hidden="true">*</span><span class="sr-only">d’un astérisque</span> sont obligatoires.</p>

                @if (session('privacy.success'))
                    <div class="mt-8 flex items-start gap-3 rounded-input bg-success-50 p-5 text-success-600" role="status">
                        <x-kh.icon name="circle-check" :size="22" class="mt-0.5" />
                        <p>{{ session('privacy.success') }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mt-8 flex items-start gap-3 rounded-input bg-kh-red-50 p-5 text-kh-red-600" role="alert">
                        <x-kh.icon name="circle-alert" :size="22" class="mt-0.5" />
                        <p>Certains champs sont à corriger ci-dessous.</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('privacy.store') }}" class="mt-8 grid gap-6" novalidate>
                    @csrf
                    <x-honeypot />
                    <div class="grid gap-6 sm:grid-cols-2">
                        <x-kh.input name="name" label="Votre nom" required autocomplete="name" />
                        <x-kh.input name="channel" label="Téléphone ou e-mail" required
                            hint="Celui que vous nous aviez donné : il sert à vous retrouver et à vous recontacter." />
                    </div>
                    <x-kh.select name="type" label="Votre demande" :options="$types" required />
                    <x-kh.textarea name="details" label="Précisions" rows="5"
                        hint="Obligatoire pour une correction : indiquez ce qui doit changer. Sinon, facultatif." />

                    <div class="flex flex-wrap items-center gap-4">
                        <x-kh.button type="submit">Envoyer ma demande</x-kh.button>
                        <span class="inline-flex items-center gap-2 text-small text-slate"><x-kh.icon name="shield-check" :size="18" class="text-kh-blue-700" /> Ces informations servent uniquement à traiter votre demande.</span>
                    </div>
                </form>
            </div>

            <aside class="space-y-6 lg:sticky lg:top-28" aria-label="Déroulement">
                <x-kh.next-steps title="Comment ça se *passe*" :steps="$steps" />
                <div class="rounded-card bg-mist p-6 text-small text-slate">
                    <p>Vous pouvez aussi nous écrire sur <a href="{{ WhatsAppLink::for('contact') }}" target="_blank" rel="noopener" class="font-medium text-kh-blue-700 underline">WhatsApp</a>.</p>
                    @if ($privacyVisible)
                        <p class="mt-3">Pour savoir quelles données nous gardons et pourquoi, lisez notre <a href="{{ route('legal.privacy') }}" class="font-medium text-kh-blue-700 underline">politique de confidentialité</a>.</p>
                    @endif
                    <p class="mt-3">La mesure d’audience se règle sur la page <a href="{{ route('legal.cookies') }}#vos-choix" class="font-medium text-kh-blue-700 underline">Cookies</a>.</p>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.public>
