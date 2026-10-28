@props(["tone" => "light"])
@php
    $nav = app(\App\Domain\Content\Services\PublicNavigation::class)->all();
    $whatsapp = \App\Domain\Platform\Support\WhatsAppLink::for('header');
    $isCurrent = fn (string $path): bool => request()->is(ltrim($path, '/'), ltrim($path, '/').'/*');
@endphp
{{-- Header — UX §4.1. Menus en <details> natifs : utilisables sans JS ; header.js ajoute Échap et clic extérieur. --}}
<header data-header data-tone="{{ $tone }}" class="kh-header sticky top-0 z-40">
    <span class="kh-progress" aria-hidden="true"></span>
    <div class="kh-container flex h-[72px] items-center gap-6">
        <a href="{{ route('home') }}" class="shrink-0 rounded-xl bg-white px-2 py-1 shadow-sm" aria-label="Kilakodon Services — accueil">
            <x-kh.logo />
        </a>

        <nav aria-label="Navigation principale" class="hidden flex-1 justify-center lg:flex">
            <ul class="flex items-center gap-1 text-small font-medium">
                <li>
                    @if ($nav['solutions'] !== [])
                        <details class="kh-disclosure relative" data-disclosure>
                            <summary class="kh-nav-link flex min-h-11 items-center gap-1 rounded-full px-3 font-semibold hover:text-kh-blue-700">
                                Solutions <x-kh.icon name="chevron-down" :size="16" class="kh-chevron" />
                            </summary>
                            <div class="kh-menu-panel absolute left-1/2 top-full mt-3 w-[640px] -translate-x-1/2 rounded-card bg-white p-3 shadow-pop">
                                <ul class="grid grid-cols-2 gap-1">
                                    @foreach ($nav['solutions'] as $item)
                                        <li>
                                            <a href="{{ $item['url'] }}" class="flex gap-3 rounded-input p-3 hover:bg-kh-blue-50">
                                                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-kh-blue-100 text-kh-blue-700">
                                                    <x-kh.icon :name="$item['icon']" :size="20" />
                                                </span>
                                                <span>
                                                    <span class="block font-medium text-ink">{{ $item['title'] }}</span>
                                                    <span class="mt-0.5 block font-normal leading-snug text-slate">{{ $item['tagline'] }}</span>
                                                </span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="mt-1 border-t border-line px-3 pt-3 pb-1">
                                    <x-kh.link-arrow :href="route('solutions.index')">Toutes les solutions</x-kh.link-arrow>
                                </div>
                            </div>
                        </details>
                    @else
                        <a href="{{ route('solutions.index') }}" class="kh-nav-link flex min-h-11 items-center rounded-full px-3 font-semibold hover:text-kh-blue-700">Solutions</a>
                    @endif
                </li>
                @if ($nav['sectors'] !== [])
                    <li>
                        <details class="kh-disclosure relative" data-disclosure>
                            <summary class="kh-nav-link flex min-h-11 items-center gap-1 rounded-full px-3 font-semibold hover:text-kh-blue-700">
                                Secteurs <x-kh.icon name="chevron-down" :size="16" class="kh-chevron" />
                            </summary>
                            <div class="kh-menu-panel absolute left-1/2 top-full mt-3 w-80 -translate-x-1/2 rounded-card bg-white p-3 shadow-pop">
                                <ul class="grid gap-1">
                                    @foreach ($nav['sectors'] as $item)
                                        <li>
                                            <a href="{{ $item['url'] }}" class="flex items-center gap-3 rounded-input p-3 hover:bg-kh-blue-50">
                                                <x-kh.icon :name="$item['icon']" :size="20" class="text-kh-blue-700" />
                                                <span class="text-ink">{{ $item['title'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </details>
                    </li>
                @endif
                @foreach ($nav['main'] as $item)
                    <li>
                        <a href="{{ url($item['path']) }}" @if ($isCurrent($item['path'])) aria-current="page" @endif
                           class="kh-nav-link flex min-h-11 items-center rounded-full px-3 font-semibold hover:text-kh-blue-700 aria-[current=page]:text-kh-blue-700">
                            {{ $item['title'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="ms-auto flex items-center gap-2 lg:ms-0">
            <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="kh-nav-link group hidden min-h-11 items-center gap-2 rounded-full px-3 text-small font-semibold hover:text-kh-blue-700 lg:inline-flex">
                <x-kh.icon name="brand:whatsapp" :size="20" class="text-whatsapp" />
                <span class="kh-underline-grow">WhatsApp</span>
            </a>
            <div class="hidden lg:block">
                <x-kh.button :href="url('/diagnostic?entry=header')">Diagnostic gratuit</x-kh.button>
            </div>
            <div class="lg:hidden">
                <x-kh.button :href="url('/diagnostic?entry=header')" size="sm" :arrow="false">Diagnostic</x-kh.button>
            </div>

            {{-- Menu mobile plein écran. --}}
            <details class="kh-disclosure lg:hidden" data-disclosure data-modal>
                <summary class="kh-burger inline-flex size-11 items-center justify-center rounded-full hover:bg-white/10" aria-label="Menu">
                    <x-kh.icon name="menu" :size="24" />
                </summary>
                <div class="kh-menu-panel fixed inset-0 z-50 flex flex-col overflow-y-auto bg-canvas">
                    <div class="kh-container flex h-[72px] shrink-0 items-center justify-between">
                        <x-kh.logo />
                        <button type="button" class="inline-flex size-11 items-center justify-center rounded-full hover:bg-mist" aria-label="Fermer le menu" data-disclosure-close>
                            <x-kh.icon name="x" :size="24" />
                        </button>
                    </div>
                    <nav aria-label="Navigation mobile" class="kh-container flex-1 py-6">
                        <ul class="space-y-1 font-display text-[1.75rem] leading-tight">
                            <li><a class="block py-2" href="{{ route('solutions.index') }}">Solutions</a></li>
                            @if ($nav['solutions'] !== [])
                                <li>
                                    <ul class="mb-3 grid gap-1 font-sans text-small">
                                        @foreach ($nav['solutions'] as $item)
                                            <li><a class="flex min-h-11 items-center gap-3 text-slate" href="{{ $item['url'] }}"><x-kh.icon :name="$item['icon']" :size="18" class="text-kh-blue-700" />{{ $item['title'] }}</a></li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endif
                            @if ($nav['sectors'] !== [])
                                <li><p class="py-2">Secteurs</p></li>
                                <li>
                                    <ul class="mb-3 grid gap-1 font-sans text-small">
                                        @foreach ($nav['sectors'] as $item)
                                            <li><a class="flex min-h-11 items-center gap-3 text-slate" href="{{ $item['url'] }}"><x-kh.icon :name="$item['icon']" :size="18" class="text-kh-blue-700" />{{ $item['title'] }}</a></li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endif
                            @foreach ($nav['main'] as $item)
                                <li><a class="block py-2" href="{{ url($item['path']) }}">{{ $item['title'] }}</a></li>
                            @endforeach
                            <li><a class="block py-2" href="{{ route('contact') }}">Contact</a></li>
                        </ul>
                    </nav>
                    <div class="kh-container grid shrink-0 gap-3 pb-[max(24px,env(safe-area-inset-bottom))]">
                        <x-kh.button :href="url('/diagnostic?entry=header')">Faire mon diagnostic gratuit</x-kh.button>
                        <x-kh.button variant="whatsapp" :href="$whatsapp" />
                    </div>
                </div>
            </details>
        </div>
    </div>
</header>
