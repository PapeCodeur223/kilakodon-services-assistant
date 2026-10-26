@php
    use App\View\Support\StructuredData;

    $crumbs = [['label' => 'Accueil', 'url' => route('home')], ['label' => 'Solutions', 'url' => route('solutions.index')]];
@endphp
<x-layouts.public
    title="Solutions : logiciels de gestion sur mesure"
    description="ERP et CRM, stocks et caisse, applications web et mobiles, audit, design : six expertises pour les entreprises du Mali, conçues et développées à Bamako."
    :schemas="[StructuredData::breadcrumbs($crumbs)]"
>
    <x-kh.page-hero
        eyebrow="Solutions"
        title="Six expertises, *un seul interlocuteur*"
        lead="Du logiciel de caisse à l'ERP complet, nous construisons ce dont vous avez besoin — ni plus, ni moins."
        :breadcrumbs="$crumbs"
    />

    <section class="pb-20 lg:pb-28" aria-label="Liste des solutions">
        <div class="kh-container">
            @if ($services->isEmpty())
                <div class="rounded-card bg-mist px-6 py-12 text-center">
                    <x-kh.halftone :size="96" class="mx-auto" />
                    <h2 class="mt-6 font-sans text-h3 font-medium">Nos fiches solutions arrivent bientôt.</h2>
                    <p class="mx-auto mt-3 max-w-xl text-slate">En attendant, le diagnostic gratuit vous indique en 3 minutes les modules adaptés à votre entreprise.</p>
                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        <x-kh.button :href="url('/diagnostic?entry=solutions')">Faire mon diagnostic gratuit</x-kh.button>
                        <x-kh.button variant="whatsapp" :href="\App\Domain\Platform\Support\WhatsAppLink::for('solutions')" />
                    </div>
                </div>
            @else
                <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($services as $service)
                        <x-kh.card :href="$service->publicUrl()" :icon="$service->icon" :title="$service->title" as="h2" data-reveal style="--i: {{ min($loop->index, 6) }}">
                            {{ $service->tagline }}
                            <x-slot:footer>
                                <span class="font-medium text-ink">Découvrir <span aria-hidden="true">→</span></span>
                                <x-kh.status-marker :status="$service->status" />
                            </x-slot:footer>
                        </x-kh.card>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <x-kh.cta-band />
</x-layouts.public>
