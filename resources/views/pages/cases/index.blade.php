@php
    use App\View\Support\StructuredData;

    $crumbs = [['label' => 'Accueil', 'url' => route('home')], ['label' => 'Réalisations', 'url' => route('cases.index')]];
@endphp
<x-layouts.public
    title="Réalisations"
    description="Des projets menés avec nos clients au Mali : contexte, défi, intervention et livrables, publiés uniquement avec leur autorisation écrite."
    :schemas="[StructuredData::breadcrumbs($crumbs)]"
>
    <x-kh.page-hero eyebrow="Réalisations" title="Des projets menés *avec nos clients*" :breadcrumbs="$crumbs"
        lead="Chaque réalisation est publiée avec l'accord écrit du client concerné." />

    <section class="pb-20 lg:pb-28" aria-label="Liste des réalisations">
        <div class="kh-container">
            @if ($caseStudies->isEmpty())
                <div class="rounded-card bg-mist px-6 py-12 text-center">
                    <x-kh.halftone :size="96" class="mx-auto" />
                    <h2 class="mt-6 font-sans text-h3 font-medium">Nos études de cas sont en préparation.</h2>
                    <p class="mx-auto mt-3 max-w-xl text-slate">Nous ne publions une réalisation qu'avec l'accord écrit du client. En attendant, essayez nos logiciels dans le Démo Lab ou parlons de votre projet.</p>
                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        <x-kh.button :href="url('/diagnostic?entry=realisations')">Faire mon diagnostic gratuit</x-kh.button>
                        <x-kh.button variant="whatsapp" :href="\App\Domain\Platform\Support\WhatsAppLink::for('realisations')" />
                    </div>
                </div>
            @else
                <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($caseStudies as $caseStudy)
                        <x-kh.card :href="$caseStudy->publicUrl()" :title="$caseStudy->title" as="h2" data-reveal style="--i: {{ min($loop->index, 6) }}">
                            {{ $caseStudy->clientLabel() }}
                            <x-slot:footer>
                                @if ($caseStudy->sector)<x-kh.badge tone="blue">{{ $caseStudy->sector->name }}</x-kh.badge>@endif
                                <x-kh.status-marker :status="$caseStudy->status" />
                            </x-slot:footer>
                        </x-kh.card>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <x-kh.cta-band />
</x-layouts.public>
