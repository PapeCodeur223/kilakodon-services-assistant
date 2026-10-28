@props(['exit' => null, 'exitLabel' => 'Quitter'])
{{-- En-tête réduit des parcours (diagnostic, brief) : logo, action de sortie, aucune navigation (UX §5.4). --}}
<header class="border-b border-line bg-white">
    <div class="mx-auto flex h-16 max-w-[820px] items-center justify-between gap-4 px-4 sm:px-6">
        <a href="{{ route('home') }}" class="shrink-0 rounded-xl" aria-label="Kilakodon Services — accueil">
            <x-kh.logo class="h-8 lg:h-9" />
        </a>
        <div class="flex items-center gap-2">
            {{ $slot }}
            <a href="{{ $exit ?? route('home') }}" class="inline-flex min-h-11 items-center gap-1.5 rounded-full px-3 text-small font-semibold text-slate hover:bg-mist hover:text-ink focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-kh-blue-500">
                <x-kh.icon name="x" :size="18" />
                {{ $exitLabel }}
            </a>
        </div>
    </div>
</header>
