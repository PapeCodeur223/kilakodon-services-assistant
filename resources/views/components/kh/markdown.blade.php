@props(['content', 'anchors' => false])
{{-- Markdown échappé (HTML saisi neutralisé) — voir App\View\Support\Markdown. --}}
<div {{ $attributes->class('kh-markdown kh-prose') }}>
    {{ $anchors ? \App\View\Support\Markdown::withAnchors($content) : \App\View\Support\Markdown::html($content) }}
</div>
