@php
    use App\View\Support\StructuredData;

    $crumbs = [
        ['label' => 'Accueil', 'url' => route('home')],
        ['label' => 'Réalisations', 'url' => route('cases.index')],
        ['label' => $caseStudy->title, 'url' => $caseStudy->publicUrl()],
    ];
@endphp
<x-layouts.public
    :title="$caseStudy->seo_title ?: $caseStudy->title"
    :description="$caseStudy->seo_description ?: \Illuminate\Support\Str::limit(strip_tags($caseStudy->context), 158)"
    :schemas="[StructuredData::breadcrumbs($crumbs)]"
>
    <x-kh.page-hero eyebrow="Réalisation" :title="$caseStudy->title" :lead="$caseStudy->clientLabel()" :breadcrumbs="$crumbs" :status="$caseStudy->status">
        <div class="flex flex-wrap gap-2">
            @if ($caseStudy->sector)<x-kh.badge tone="blue">{{ $caseStudy->sector->name }}</x-kh.badge>@endif
            @foreach ($caseStudy->services as $service)
                <x-kh.badge>{{ $service->title }}</x-kh.badge>
            @endforeach
        </div>
    </x-kh.page-hero>

    {{-- Structure UX §5.7 : Contexte → Défi → Intervention → Livrables → Résultats (si vérifiés) → Témoignage (si autorisé). --}}
    <section class="pb-20 lg:pb-28">
        <div class="kh-container grid gap-12 lg:grid-cols-[1fr_20rem]">
            <article class="space-y-12">
                @foreach (['Contexte' => $caseStudy->context, 'Défi' => $caseStudy->challenge, 'Intervention' => $caseStudy->intervention] as $heading => $text)
                    <div data-reveal>
                        <h2 class="text-h2 text-ink">{{ $heading }}</h2>
                        <x-kh.markdown :content="$text" class="mt-4 text-slate" />
                    </div>
                @endforeach

                <div data-reveal>
                    <h2 class="text-h2 text-ink">Livrables</h2>
                    <ul class="mt-4 space-y-2">
                        @foreach ($caseStudy->deliverables as $deliverable)
                            <li class="flex items-start gap-2"><x-kh.icon name="check" :size="18" class="mt-1 text-success-600" />{{ $deliverable }}</li>
                        @endforeach
                    </ul>
                </div>

                @if ($caseStudy->showsResults())
                    <div data-reveal>
                        <h2 class="text-h2 text-ink">Résultats</h2>
                        <x-kh.markdown :content="$caseStudy->results" class="mt-4 text-slate" />
                    </div>
                @endif

                @if ($caseStudy->showsQuote())
                    <figure class="rounded-card bg-kh-blue-50 p-8 text-kh-blue-900" data-reveal>
                        <blockquote class="font-display text-h3">« {{ $caseStudy->quote }} »</blockquote>
                        @if ($caseStudy->quote_author)<figcaption class="mt-4 text-small">— {{ $caseStudy->quote_author }}</figcaption>@endif
                    </figure>
                @endif
            </article>

            <aside class="lg:sticky lg:top-24 lg:self-start">
                <x-kh.card title="Un projet similaire ?">
                    Le diagnostic gratuit vous indique en 3 minutes les modules adaptés à votre entreprise.
                    <x-slot:footer>
                        <x-kh.button :href="url('/diagnostic?entry=realisation')" size="sm">Faire mon diagnostic</x-kh.button>
                    </x-slot:footer>
                </x-kh.card>
            </aside>
        </div>
    </section>
</x-layouts.public>
