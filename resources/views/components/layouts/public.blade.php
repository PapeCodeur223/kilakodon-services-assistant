@props([
    'title' => null,
    'description' => 'ERP, CRM, gestion de stock et caisse, applications web et mobiles conçus à Bamako pour les entreprises du Mali et d\'Afrique de l\'Ouest. Diagnostic gratuit en 3 minutes.',
    'noindex' => false,
    'chrome' => 'full',
])
@php
    $pageTitle = $title
        ? \App\View\Support\Accent::plain($title).' — Kilakodon Services'
        : 'Kilakodon Services — Logiciels de gestion sur mesure à Bamako';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    {{-- La classe .js active les états initiaux des animations : sans JS, tout reste visible. --}}
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">document.documentElement.classList.add('js')</script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $description }}">
    @if ($noindex)
        <meta name="robots" content="noindex, nofollow">
    @endif
    <meta name="theme-color" content="white">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('images/brand/favicon-32.png') }}" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/apple-touch-icon.png') }}">
    @isset($head)
        {{ $head }}
    @endisset
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-canvas text-ink">
    <a href="#contenu" class="kh-skip-link rounded-full bg-kh-blue-700 px-4 py-2 font-medium text-white">Aller au contenu</a>

    @if ($chrome === 'full')
        <x-kh.header />
    @endif

    <main id="contenu" tabindex="-1" class="focus:outline-none">
        {{ $slot }}
    </main>

    @if ($chrome === 'full')
        <x-kh.footer />
    @endif
</body>
</html>
