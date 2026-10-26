@props(['href' => null, 'icon' => null, 'title' => null, 'as' => 'h3'])
@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class([
    'block rounded-card bg-mist px-6 py-7 text-ink',
    'kh-card-link focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-kh-blue-500' => $href,
]) }}>
    @if ($icon)
        <span class="kh-card-icon mb-5 inline-flex size-11 items-center justify-center rounded-full bg-kh-blue-100 text-kh-blue-700">
            <x-kh.icon :name="$icon" :size="22" />
        </span>
    @endif
    @if ($title)
        <{{ $as }} class="text-h3 font-medium text-ink">{{ $title }}</{{ $as }}>
    @endif
    @if ($slot->isNotEmpty())
        <div @class(['text-slate', 'mt-2' => $title])>{{ $slot }}</div>
    @endif
    @isset($footer)
        <div class="mt-5">{{ $footer }}</div>
    @endisset
</{{ $tag }}>
