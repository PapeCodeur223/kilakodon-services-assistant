@props(['caseStudy', 'featured' => false, 'as' => 'h2'])
@php
    /** @var \App\Domain\Content\Models\CaseStudy $caseStudy */
    $sector = $caseStudy->sector;
    $deliverables = array_slice($caseStudy->deliverables ?? [], 0, $featured ? 4 : 3);
@endphp
{{-- Carte de réalisation : visuel du secteur (ou couverture), titre, client, livrables clés. --}}
<a href="{{ $caseStudy->publicUrl() }}" {{ $attributes->class([
    'kh-card group flex h-full flex-col overflow-hidden rounded-card border border-line bg-white focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-kh-blue-500',
    'lg:flex-row' => $featured,
]) }}>
    <div @class([
        'kh-dark relative flex shrink-0 items-end overflow-hidden p-6',
        'h-44' => ! $featured,
        'h-56 lg:h-auto lg:w-[46%] lg:p-10' => $featured,
    ])>
        @if ($caseStudy->cover_path)
            <img src="{{ asset('storage/'.$caseStudy->cover_path) }}" alt="" width="800" height="450" loading="lazy" class="absolute inset-0 size-full object-cover opacity-60 transition-transform duration-500 group-hover:scale-105">
        @else
            <div class="kh-grid-bg pointer-events-none absolute inset-0" aria-hidden="true"></div>
            @if ($featured)
                <x-kh.icon :name="$sector?->icon ?: 'layout-dashboard'" :size="220" class="pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-white/5 transition-transform duration-700 group-hover:scale-110" />
            @endif
            <div class="pointer-events-none absolute -right-10 -top-12 transition-transform duration-700 group-hover:rotate-12" aria-hidden="true">
                <x-kh.halftone :size="$featured ? 300 : 200" tone="blue-900" class="opacity-80" />
            </div>
        @endif
        <div class="relative flex w-full items-end justify-between gap-4">
            <span @class([
                'flex items-center justify-center rounded-2xl bg-white/10 text-white ring-1 ring-white/20 backdrop-blur-sm transition-transform duration-300 group-hover:-translate-y-1',
                'size-14' => ! $featured,
                'size-20' => $featured,
            ])>
                <x-kh.icon :name="$sector?->icon ?: 'layout-dashboard'" :size="$featured ? 36 : 26" />
            </span>
            @if ($sector)
                <span class="rounded-full bg-white/10 px-3 py-1 text-tag font-semibold uppercase text-kh-blue-100 ring-1 ring-white/15">{{ $sector->name }}</span>
            @endif
        </div>
    </div>

    <div @class(['flex flex-1 flex-col p-6 sm:p-7', 'lg:p-10' => $featured])>
        <div class="flex flex-wrap items-center gap-2">
            @if ($featured)<x-kh.badge tone="blue">À la une</x-kh.badge>@endif
            <x-kh.status-marker :status="$caseStudy->status" />
        </div>
        <{{ $as }} @class(['mt-3 text-ink', 'text-h3' => ! $featured, 'text-h2' => $featured])>{{ $caseStudy->title }}</{{ $as }}>
        <p class="mt-2 text-small text-slate">{{ $caseStudy->clientLabel() }}</p>

        @if ($deliverables !== [])
            <ul class="mt-5 flex flex-wrap gap-2" aria-label="Livrables">
                @foreach ($deliverables as $deliverable)
                    <li class="inline-flex items-center gap-1.5 rounded-full bg-fog px-3 py-1 text-small text-ink"><x-kh.icon name="check" :size="14" class="text-kh-blue-700" />{{ $deliverable }}</li>
                @endforeach
            </ul>
        @endif

        <span class="mt-auto inline-flex items-center gap-1.5 pt-6 font-medium text-kh-blue-700">
            <span class="kh-underline-grow">Voir la réalisation</span><span class="kh-arrow" aria-hidden="true">→</span>
        </span>
    </div>
</a>
