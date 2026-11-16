@props([
    'steps' => [],
    'title' => null,
    'id' => null,
])
{{-- Frise verticale numérotée (style page Méthode) : « ce qui se passe ensuite ». --}}
<div {{ $attributes->class('rounded-card border border-line bg-white p-7 sm:p-8') }}>
    @if ($title)
        <h2 @if ($id) id="{{ $id }}" @endif class="font-display text-h3 font-extrabold text-ink">{{ \App\View\Support\Accent::html($title) }}</h2>
    @endif
    <ol @class(['relative', 'mt-7' => $title])>
        <span class="pointer-events-none absolute top-2 bottom-2 left-5 w-0.5 bg-linear-to-b from-kh-blue-700 via-kh-blue-300 to-kh-red-400" aria-hidden="true"></span>
        @foreach ($steps as $i => $step)
            <li class="relative flex gap-5 pb-7 last:pb-0" data-reveal style="--i: {{ $i }}">
                <span class="relative z-10 flex size-10 shrink-0 items-center justify-center rounded-full bg-kh-blue-700 font-display text-small font-extrabold text-white shadow-glow ring-4 ring-white" aria-hidden="true">{{ $i + 1 }}</span>
                <div class="pt-1.5">
                    <h3 class="font-semibold text-ink"><span class="sr-only">Étape {{ $i + 1 }} : </span>{{ $step['title'] }}</h3>
                    @if (! empty($step['text']))
                        <p class="mt-1 text-small text-slate">{{ $step['text'] }}</p>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</div>
