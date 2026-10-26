@props([
    'tone' => 'canvas',
    'eyebrow' => null,
    'title' => null,
    'lead' => null,
    'as' => 'h2',
    'id' => null,
    'centered' => false,
])
<section @if ($id) id="{{ $id }}" @endif {{ $attributes->class([
    'kh-section relative overflow-hidden',
    'bg-canvas' => $tone === 'canvas',
    'bg-fog' => $tone === 'fog',
    'bg-kh-blue-950 text-kh-blue-200' => $tone === 'blue',
]) }} @if ($title && $id) aria-labelledby="{{ $id }}-title" @endif>
    @isset($decor)
        {{ $decor }}
    @endisset
    <div class="kh-container relative">
        @if ($eyebrow || $title || $lead)
            <header @class(['mb-10 max-w-3xl lg:mb-14', 'mx-auto text-center' => $centered])>
                @if ($eyebrow)
                    <x-kh.eyebrow :tone="$tone === 'blue' ? 'inverse' : 'blue'" class="mb-4" data-reveal>{{ $eyebrow }}</x-kh.eyebrow>
                @endif
                @if ($title)
                    <{{ $as }} @if ($id) id="{{ $id }}-title" @endif @class(['text-h2', 'text-white' => $tone === 'blue', 'text-ink' => $tone !== 'blue']) data-reveal style="--i: 1">{{ \App\View\Support\Accent::html($title) }}</{{ $as }}>
                @endif
                @if ($lead)
                    <p @class(['mt-4 text-lead', 'text-slate' => $tone !== 'blue']) data-reveal style="--i: 2">{{ $lead }}</p>
                @endif
            </header>
        @endif
        {{ $slot }}
    </div>
</section>
