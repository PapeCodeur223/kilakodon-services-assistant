@props(['tone' => 'blue', 'title' => null, 'as' => 'h3'])
{{-- Ton rouge : une seule carte par page au maximum (UX §4). --}}
<div {{ $attributes->class([
    'kh-card relative overflow-hidden rounded-card px-7 py-8',
    'bg-kh-blue-50 text-kh-blue-900' => $tone === 'blue',
    'bg-linear-to-br from-kh-red-50 to-white text-kh-red-900 ring-1 ring-kh-red-500/15' => $tone === 'red',
]) }}>
    @if ($title)
        <{{ $as }} class="font-display text-h3 font-extrabold">{{ \App\View\Support\Accent::html($title) }}</{{ $as }}>
    @endif
    <div @class(['mt-3' => $title])>{{ $slot }}</div>
</div>
