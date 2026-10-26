@props(['tone' => 'blue'])
<p {{ $attributes->class([
    'inline-flex items-center gap-2 text-tag font-medium uppercase',
    'text-kh-blue-700' => $tone === 'blue',
    'text-kh-blue-200' => $tone === 'inverse',
]) }}>
    <span class="size-1.5 shrink-0 rounded-full bg-kh-red-500" aria-hidden="true"></span>
    <span>{{ $slot }}</span>
</p>
