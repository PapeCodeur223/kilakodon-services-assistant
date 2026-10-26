@props([
    'title',
    'eyebrow' => null,
    'lead' => null,
    'breadcrumbs' => [],
    'status' => null,
])
{{-- En-tête des pages internes : fil d'Ariane, sur-titre, H1 Fraunces, chapeau. --}}
<section class="relative overflow-hidden pt-8 pb-14 lg:pt-12 lg:pb-20">
    <x-kh.halftone :size="380" class="absolute -right-28 -top-10 hidden md:block" animate />
    <div class="kh-container relative">
        @if ($breadcrumbs)
            <x-kh.breadcrumb :items="$breadcrumbs" class="mb-10" />
        @endif
        <div class="max-w-3xl">
            <div class="flex flex-wrap items-center gap-3">
                @if ($eyebrow)
                    <x-kh.eyebrow data-reveal>{{ $eyebrow }}</x-kh.eyebrow>
                @endif
                <x-kh.status-marker :status="$status" />
            </div>
            <h1 @class(['text-h1 text-ink', 'mt-4' => $eyebrow]) data-reveal style="--i: 1">{{ \App\View\Support\Accent::html($title) }}</h1>
            @if ($lead)
                <p class="kh-prose mt-5 text-lead text-slate" data-reveal style="--i: 2">{{ $lead }}</p>
            @endif
            @if ($slot->isNotEmpty())
                <div class="mt-8" data-reveal style="--i: 3">{{ $slot }}</div>
            @endif
        </div>
    </div>
</section>
