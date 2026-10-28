@props(['href' => null, 'icon' => null, 'title' => null, 'as' => 'h3', 'tone' => 'light'])
@php $tag = $href ? 'a' : 'div'; @endphp
{{-- Carte : élévation et halo qui suit la souris au survol (avec ou sans lien). --}}
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class([
    'kh-card group block overflow-hidden rounded-card border px-7 py-8',
    'border-line bg-white text-ink' => $tone === 'light',
    'border-transparent bg-mist text-ink' => $tone === 'muted',
    'kh-dark border-white/10 text-white' => $tone === 'dark',
    'focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-kh-blue-500' => $href,
]) }}>
    @if ($icon)
        <span @class([
            'kh-card-icon mb-6 inline-flex size-12 items-center justify-center rounded-2xl',
            'bg-kh-blue-50 text-kh-blue-700' => $tone !== 'dark',
            'bg-white/10 text-white' => $tone === 'dark',
        ])>
            <x-kh.icon :name="$icon" :size="24" />
        </span>
    @endif
    @if ($title)
        <{{ $as }} @class(['text-h3', 'text-ink' => $tone !== 'dark', 'text-white' => $tone === 'dark'])>{{ $title }}</{{ $as }}>
    @endif
    @if ($slot->isNotEmpty())
        <div @class(['text-slate' => $tone !== 'dark', 'text-kh-blue-200' => $tone === 'dark', 'mt-3' => $title])>{{ $slot }}</div>
    @endif
    @isset($footer)
        <div class="mt-6 flex flex-wrap items-center gap-3">{{ $footer }}</div>
    @endisset
</{{ $tag }}>
