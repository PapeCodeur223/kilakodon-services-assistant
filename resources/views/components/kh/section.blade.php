@props([
    'tone' => 'canvas',
    'eyebrow' => null,
    'title' => null,
    'lead' => null,
    'as' => 'h2',
    'id' => null,
    'centered' => false,
])
@php $dark = in_array($tone, ['blue', 'dark'], true); @endphp
<section @if ($id) id="{{ $id }}" @endif {{ $attributes->class([
    'kh-section relative overflow-hidden',
    'bg-canvas' => $tone === 'canvas',
    'bg-fog' => $tone === 'fog',
    'kh-dark' => $dark,
]) }} @if ($title && $id) aria-labelledby="{{ $id }}-title" @endif>
    @if ($dark)
        <div class="kh-grid-bg pointer-events-none absolute inset-0" aria-hidden="true"></div>
    @endif
    @isset($decor)
        {{ $decor }}
    @endisset
    <div class="kh-container relative">
        @if ($eyebrow || $title || $lead)
            <header @class(['mb-12 max-w-3xl lg:mb-16', 'mx-auto text-center' => $centered])>
                @if ($eyebrow)
                    <x-kh.eyebrow :tone="$dark ? 'inverse' : 'blue'" class="mb-5" data-reveal>{{ $eyebrow }}</x-kh.eyebrow>
                @endif
                @if ($title)
                    <{{ $as }} @if ($id) id="{{ $id }}-title" @endif @class(['text-h2', 'text-white' => $dark, 'text-ink' => ! $dark]) data-reveal style="--i: 1">{{ \App\View\Support\Accent::html($title) }}</{{ $as }}>
                @endif
                @if ($lead)
                    <p @class(['mt-5 text-lead', 'text-slate' => ! $dark, 'text-kh-blue-200' => $dark]) data-reveal style="--i: 2">{{ $lead }}</p>
                @endif
            </header>
        @endif
        {{ $slot }}
    </div>
</section>
