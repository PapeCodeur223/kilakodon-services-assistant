@props(['items', 'tone' => 'light'])
{{-- $items : list<array{label: string, url: string}> — le dernier élément est la page courante. --}}
<nav aria-label="Fil d'Ariane" {{ $attributes->class(['text-small', 'text-slate' => $tone === 'light', 'text-kh-blue-300' => $tone === 'dark']) }}>
    <ol class="flex flex-wrap items-center gap-1.5">
        @foreach ($items as $item)
            <li class="flex items-center gap-1.5">
                @if (! $loop->last)
                    <a href="{{ $item['url'] }}" @class(['hover:underline', 'hover:text-kh-blue-700' => $tone === 'light', 'hover:text-white' => $tone === 'dark'])>{{ $item['label'] }}</a>
                    <span aria-hidden="true">/</span>
                @else
                    <span aria-current="page" @class(['text-ink' => $tone === 'light', 'text-white' => $tone === 'dark'])>{{ \App\View\Support\Accent::plain($item['label']) }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
