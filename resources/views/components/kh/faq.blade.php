@props(['items'])
{{-- $items : iterable<array{q: string, a: string}>. <details> natifs : fonctionnent sans JS (UX §4). --}}
<div {{ $attributes->class('divide-y divide-line border-y border-line') }}>
    @foreach ($items as $item)
        <details class="kh-faq kh-disclosure group">
            <summary class="flex min-h-14 items-center justify-between gap-6 py-5 text-left text-body font-medium text-ink hover:text-kh-blue-700">
                <span>{{ $item['q'] }}</span>
                <span class="kh-faq-plus inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-mist text-kh-blue-700" aria-hidden="true">
                    <x-kh.icon name="plus" :size="18" />
                </span>
            </summary>
            <div class="kh-prose pb-6 text-slate">{{ $item['a'] }}</div>
        </details>
    @endforeach
</div>
