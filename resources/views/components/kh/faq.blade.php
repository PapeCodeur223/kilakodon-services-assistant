@props(['items'])
{{-- $items : iterable<array{q: string, a: string}>. <details> natifs : fonctionnent sans JS (UX §4). --}}
<div {{ $attributes->class('space-y-3') }}>
    @foreach ($items as $item)
        <details class="kh-faq kh-disclosure group rounded-2xl border border-line bg-white transition-colors duration-200 hover:border-kh-blue-200 open:border-kh-blue-200 open:shadow-[var(--shadow-hover)]">
            <summary class="flex min-h-16 items-center justify-between gap-6 px-6 py-4 text-left font-display text-[1.05rem] font-bold text-ink transition-colors group-hover:text-kh-blue-700">
                <span>{{ $item['q'] }}</span>
                <span class="kh-faq-plus inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-kh-blue-50 text-kh-blue-700" aria-hidden="true">
                    <x-kh.icon name="plus" :size="18" />
                </span>
            </summary>
            <div class="kh-prose px-6 pb-6 text-slate">{{ $item['a'] }}</div>
        </details>
    @endforeach
</div>
