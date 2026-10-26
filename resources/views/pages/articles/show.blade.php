@php
    use App\View\Support\StructuredData;

    $crumbs = [
        ['label' => 'Accueil', 'url' => route('home')],
        ['label' => 'Ressources', 'url' => route('articles.index')],
        ['label' => $article->title, 'url' => $article->publicUrl()],
    ];
    $tz = config('kh.display_timezone');
@endphp
<x-layouts.public
    :title="$article->seo_title ?: $article->title"
    :description="$article->seo_description ?: $article->excerpt"
    type="article"
    :schemas="[StructuredData::article($article), StructuredData::breadcrumbs($crumbs)]"
>
    <article>
        <header class="pt-8 pb-10 lg:pt-12">
            <div class="kh-container">
                <x-kh.breadcrumb :items="$crumbs" class="mb-10" />
                <div class="mx-auto max-w-[68ch]">
                    <p class="flex flex-wrap items-center gap-2 text-small text-slate">
                        <x-kh.badge tone="blue">{{ $article->category->label() }}</x-kh.badge>
                        <span>{{ $article->reading_minutes }} min de lecture</span>
                        <x-kh.status-marker :status="$article->status" />
                    </p>
                    <h1 class="mt-5 text-h1 text-ink" data-reveal>{{ $article->title }}</h1>
                    <p class="mt-5 text-lead text-slate" data-reveal style="--i: 1">{{ $article->excerpt }}</p>
                    <p class="mt-6 text-small text-slate">
                        Par {{ $article->authorName() }}
                        @if ($article->published_at)
                            · <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->timezone($tz)->translatedFormat('j F Y') }}</time>
                        @endif
                    </p>
                </div>
            </div>
        </header>

        <div class="kh-container pb-16">
            <x-kh.markdown :content="$article->body" class="mx-auto max-w-[68ch] text-body text-ink" />

            {{-- Bloc CTA de fin d'article (UX §5.7). --}}
            <aside class="mx-auto mt-14 max-w-[68ch]">
                <x-kh.card-accent tone="blue" title="Et dans *votre* entreprise ?">
                    <p>Le diagnostic gratuit vous indique en 3 minutes vos priorités et les modules adaptés.</p>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <x-kh.button :href="url('/diagnostic?entry=article')">Faire mon diagnostic gratuit</x-kh.button>
                        <x-kh.button variant="whatsapp" :href="\App\Domain\Platform\Support\WhatsAppLink::for('article')" />
                    </div>
                </x-kh.card-accent>
            </aside>
        </div>
    </article>

    @if ($related->isNotEmpty())
        <x-kh.section tone="fog" title="À lire aussi">
            <div class="grid gap-5 md:grid-cols-2">
                @foreach ($related as $other)
                    <x-kh.card :href="$other->publicUrl()" :title="$other->title">{{ $other->excerpt }}</x-kh.card>
                @endforeach
            </div>
        </x-kh.section>
    @endif
</x-layouts.public>
