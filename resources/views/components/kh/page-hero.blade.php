@props([
    'title',
    'eyebrow' => null,
    'lead' => null,
    'breadcrumbs' => [],
    'status' => null,
])
{{-- En-tête des pages internes : fond sombre « agence », trame, sphère en rotation lente. --}}
<section class="kh-dark relative -mt-[72px] overflow-hidden pt-[calc(72px+2.5rem)] pb-20 lg:pt-[calc(72px+4rem)] lg:pb-28">
    <div class="kh-grid-bg pointer-events-none absolute inset-0" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -right-24 -top-16 hidden md:block" aria-hidden="true" data-parallax="0.12">
        <x-kh.halftone :size="460" tone="blue-900" class="kh-spin opacity-80" animate />
    </div>
    <div class="kh-container relative">
        @if ($breadcrumbs)
            <x-kh.breadcrumb :items="$breadcrumbs" tone="dark" class="mb-10" />
        @endif
        <div class="max-w-3xl">
            <div class="flex flex-wrap items-center gap-3">
                @if ($eyebrow)
                    <x-kh.eyebrow tone="inverse" data-reveal>{{ $eyebrow }}</x-kh.eyebrow>
                @endif
                <x-kh.status-marker :status="$status" />
            </div>
            <h1 @class(['text-h1 text-white', 'mt-6' => $eyebrow]) data-reveal style="--i: 1">{{ \App\View\Support\Accent::html($title) }}</h1>
            @if ($lead)
                <p class="kh-prose mt-6 text-lead text-kh-blue-200" data-reveal style="--i: 2">{{ $lead }}</p>
            @endif
            @if ($slot->isNotEmpty())
                <div class="mt-9" data-reveal style="--i: 3">{{ $slot }}</div>
            @endif
        </div>
    </div>
</section>
