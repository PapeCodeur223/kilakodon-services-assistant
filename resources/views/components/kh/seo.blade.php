@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'image' => null,
    'type' => 'website',
    'noindex' => false,
    'schemas' => [],
])
@php
    // Gabarit CONTENT §1 : « {Titre} — Kilakodon Services ».
    $fullTitle = $title
        ? \App\View\Support\Accent::plain($title).' — Kilakodon Services'
        : 'Kilakodon Services — Logiciels de gestion sur mesure à Bamako';
    $description ??= 'ERP, CRM, gestion de stock et caisse, applications web et mobiles conçus à Bamako pour les entreprises du Mali et d\'Afrique de l\'Ouest. Diagnostic gratuit en 3 minutes.';
    $canonical ??= url()->current();
    $image ??= asset('images/brand/og-default.png');
    $noindex = $noindex || \App\Domain\Content\Support\ContentPreview::enabled();
@endphp
<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ $canonical }}">
@if ($noindex)
    <meta name="robots" content="noindex, nofollow">
@endif
<meta property="og:type" content="{{ $type }}">
<meta property="og:site_name" content="Kilakodon Services">
<meta property="og:locale" content="fr_FR">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $image }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $fullTitle }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $image }}">
@foreach (array_filter($schemas) as $schema)
    <script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">{!! \App\View\Support\StructuredData::encode($schema) !!}</script>
@endforeach
