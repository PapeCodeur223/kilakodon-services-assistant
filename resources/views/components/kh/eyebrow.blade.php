@props(['tone' => 'blue'])
<p {{ $attributes->class([
    'inline-flex items-center gap-2.5 rounded-full py-1.5 ps-2.5 pe-3.5 text-tag font-semibold uppercase',
    'bg-kh-blue-50 text-kh-blue-700 ring-1 ring-kh-blue-100' => $tone === 'blue',
    'bg-white/8 text-kh-blue-200 ring-1 ring-white/15' => $tone === 'inverse',
]) }}>
    <span class="relative flex size-2 shrink-0" aria-hidden="true">
        <span class="kh-pulse absolute inset-0 rounded-full bg-kh-red-500"></span>
        <span class="relative size-2 rounded-full bg-kh-red-500"></span>
    </span>
    <span>{{ $slot }}</span>
</p>
