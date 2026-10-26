@props(['tone' => 'blue', 'title' => null, 'as' => 'h3'])
{{-- Ton rouge : une seule carte par page au maximum (UX §4). --}}
<div {{ $attributes->class([
    'rounded-card px-6 py-7',
    'bg-kh-blue-50 text-kh-blue-900' => $tone === 'blue',
    'bg-kh-red-50 text-kh-red-900' => $tone === 'red',
]) }}>
    @if ($title)
        <{{ $as }} class="font-display text-h3">{{ \App\View\Support\Accent::html($title) }}</{{ $as }}>
    @endif
    <div @class(['mt-3' => $title])>{{ $slot }}</div>
</div>
