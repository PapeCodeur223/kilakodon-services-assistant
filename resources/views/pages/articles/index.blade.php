@php
    use App\View\Support\StructuredData;

    $crumbs = [['label' => 'Accueil', 'url' => route('home')], ['label' => 'Ressources', 'url' => route('articles.index')]];
    $tz = config('kh.display_timezone');
@endphp
<x-layouts.public
    title="Ressources : conseils de gestion"
    description="Guides pratiques pour mieux gérer votre stock, votre caisse et vos outils : passer d'Excel à un logiciel, choisir entre sur-mesure et standard, réalités du terrain au Mali."
    :schemas="[StructuredData::breadcrumbs($crumbs)]"
>
    <x-kh.page-hero eyebrow="Ressources" title="Des conseils concrets pour *mieux gérer*" :breadcrumbs="$crumbs"
        lead="Stock, caisse, outils de gestion : des guides pratiques, écrits à partir du terrain." />

    <section class="pb-20 lg:pb-28" aria-label="Articles">
        <div class="kh-container">
            @if ($articles->isEmpty())
                <div class="rounded-card bg-mist px-6 py-12 text-center">
                    <x-kh.halftone :size="96" class="mx-auto" />
                    <h2 class="mt-6 font-sans text-h3 font-medium">Nos premiers articles sont en cours de relecture.</h2>
                    <p class="mx-auto mt-3 max-w-xl text-slate">Revenez bientôt, ou posez-nous directement votre question.</p>
                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        <x-kh.button :href="url('/diagnostic?entry=ressources')">Faire mon diagnostic gratuit</x-kh.button>
                        <x-kh.button variant="whatsapp" :href="\App\Domain\Platform\Support\WhatsAppLink::for('ressources')" />
                    </div>
                </div>
            @else
                <ul class="divide-y divide-line border-y border-line">
                    @foreach ($articles as $article)
                        <li data-reveal style="--i: {{ min($loop->index, 6) }}">
                            <a href="{{ $article->publicUrl() }}" class="group grid gap-3 py-8 md:grid-cols-[12rem_1fr] md:gap-10">
                                <p class="flex flex-wrap items-center gap-2 text-small text-slate">
                                    <x-kh.badge tone="blue">{{ $article->category->label() }}</x-kh.badge>
                                    <span>{{ $article->reading_minutes }} min de lecture</span>
                                </p>
                                <div>
                                    <h2 class="text-h3 text-ink group-hover:text-kh-blue-700"><span class="font-display">{{ $article->title }}</span></h2>
                                    <p class="kh-prose mt-2 text-slate">{{ $article->excerpt }}</p>
                                    <p class="mt-3 flex flex-wrap items-center gap-2 text-small text-slate">
                                        @if ($article->published_at)
                                            <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->timezone($tz)->translatedFormat('j F Y') }}</time> ·
                                        @endif
                                        {{ $article->authorName() }}
                                        <x-kh.status-marker :status="$article->status" />
                                    </p>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
</x-layouts.public>
