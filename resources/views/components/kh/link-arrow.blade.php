@props(['href'])
<a href="{{ $href }}" {{ $attributes->class('group inline-flex items-center gap-1.5 font-medium text-ink hover:text-kh-blue-700') }}>
    <span class="kh-underline-grow">{{ $slot }}</span>
    <span class="kh-arrow" aria-hidden="true">→</span>
</a>
