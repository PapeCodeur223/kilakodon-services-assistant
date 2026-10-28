@php
    use App\Domain\Content\Enums\PageTemplate;
    use App\View\Support\Markdown;
    use App\View\Support\StructuredData;

    $crumbs = [['label' => 'Accueil', 'url' => route('home')], ['label' => $page->title, 'url' => $page->publicUrl()]];
    $isLegal = $page->template === PageTemplate::Legal;
    $toc = $isLegal ? Markdown::headings($page->body) : [];
    $tz = config('kh.display_timezone');
@endphp
<x-layouts.public :header-tone="$isLegal ? 'light' : 'dark'"
    :title="$page->seo_title ?: $page->title"
    :description="$page->seo_description"
    :schemas="[StructuredData::breadcrumbs($crumbs)]"
>
    @if ($isLegal)
        {{-- Gabarit légal : texte simple et sommaire ancré (UX §5.7). --}}
        <section class="pt-8 pb-20 lg:pt-12 lg:pb-28">
            <div class="kh-container">
                <x-kh.breadcrumb :items="$crumbs" class="mb-10" />
                <div class="flex flex-wrap items-center gap-3"><x-kh.status-marker :status="$page->status" /></div>
                <h1 class="mt-4 text-h1 text-ink">{{ \App\View\Support\Accent::html($page->title) }}</h1>
                @if ($page->updated_at)
                    <p class="mt-3 text-small text-slate">Dernière mise à jour : {{ $page->updated_at->timezone($tz)->translatedFormat('j F Y') }}</p>
                @endif
                <div class="mt-12 grid gap-12 lg:grid-cols-[16rem_1fr]">
                    @if ($toc !== [])
                        <nav aria-label="Sommaire" class="lg:sticky lg:top-24 lg:self-start">
                            <p class="text-tag font-medium uppercase text-slate">Sommaire</p>
                            <ol class="mt-4 space-y-2 text-small">
                                @foreach ($toc as $item)
                                    <li><a href="#{{ $item['id'] }}" class="text-ink hover:text-kh-blue-700 hover:underline">{{ $item['title'] }}</a></li>
                                @endforeach
                            </ol>
                        </nav>
                    @endif
                    <x-kh.markdown :content="$page->body" :anchors="true" class="text-ink" />
                </div>
            </div>
        </section>
    @else
        <x-kh.page-hero :title="$page->title" :breadcrumbs="$crumbs" :status="$page->status" />
        <section class="pb-20 lg:pb-28">
            <div class="kh-container">
                <x-kh.markdown :content="$page->body" class="text-lead text-ink" />
            </div>
        </section>
        <x-kh.cta-band />
    @endif
</x-layouts.public>
