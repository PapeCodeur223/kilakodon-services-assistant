@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'image' => null,
    'type' => 'website',
    'noindex' => false,
    'schemas' => [],
    'chrome' => 'full',
    'headerTone' => 'dark',
])
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    {{-- La classe .js active les états initiaux des animations : sans JS, tout reste visible. --}}
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">document.documentElement.classList.add('js')</script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Jeton CSRF : lu par la mesure first-party (sendBeacon vers /api/events). --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (app(\App\Domain\Platform\Analytics\AudienceConsent::class)->granted())
        {{-- Mesure d'audience acceptée : le script de mesure ne s'active qu'avec cette balise (Q-011). --}}
        <meta name="kh-analytics" content="on">
    @endif
    <x-kh.seo :title="$title" :description="$description" :canonical="$canonical" :image="$image" :type="$type" :noindex="$noindex" :schemas="$schemas" />
    <meta name="theme-color" content="white">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('images/brand/favicon-32.png') }}" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/apple-touch-icon.png') }}">
    @isset($head)
        {{ $head }}
    @endisset
    {{-- Préchargement de la police d'affichage (Plus Jakarta Sans, D-013). --}}
    <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ \Illuminate\Support\Facades\Vite::asset('node_modules/@fontsource-variable/plus-jakarta-sans/files/plus-jakarta-sans-latin-wght-normal.woff2') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-canvas text-ink">
    <a href="#contenu" class="kh-skip-link rounded-full bg-kh-blue-700 px-4 py-2 font-medium text-white">Aller au contenu</a>

    @if (\App\Domain\Content\Support\ContentPreview::enabled())
        <div class="bg-warning-50 text-warning-600">
            <p class="kh-container flex items-center gap-2 py-2 text-small">
                <x-kh.icon name="info" :size="18" />
                Prévisualisation équipe : les brouillons et les éléments « [À VALIDER] » sont visibles. Le public ne voit que les contenus publiés.
            </p>
        </div>
    @endif

    @if ($chrome === 'full')
        <x-kh.header :tone="$headerTone" />
    @endif

    <main id="contenu" tabindex="-1" class="focus:outline-none">
        {{ $slot }}
    </main>

    @if ($chrome === 'full')
        <x-kh.footer />
        {{-- Assistant : absent si l'interrupteur est coupé ou le budget atteint ; le site n'en dépend pas. --}}
        @if (app(\App\Domain\Assistant\Conversation\AssistantGuard::class)->enabled())
            <x-kh.assistant />
        @endif
    @endif

    <x-kh.cookie-banner />
</body>
</html>
