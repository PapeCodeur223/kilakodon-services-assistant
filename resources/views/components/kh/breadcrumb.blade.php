@props(['items'])
{{-- $items : list<array{label: string, url: string}> — le dernier élément est la page courante. --}}
<nav aria-label="Fil d'Ariane" {{ $attributes->class('text-small text-slate') }}>
    <ol class="flex flex-wrap items-center gap-1.5">
        @foreach ($items as $item)
            <li class="flex items-center gap-1.5">
                @if (! $loop->last)
                    <a href="{{ $item['url'] }}" class="hover:text-kh-blue-700 hover:underline">{{ $item['label'] }}</a>
                    <span aria-hidden="true">/</span>
                @else
                    <span aria-current="page" class="text-ink">{{ \App\View\Support\Accent::plain($item['label']) }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
