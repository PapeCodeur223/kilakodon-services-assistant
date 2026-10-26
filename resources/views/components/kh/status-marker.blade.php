@props(['status' => null])
{{-- Visible uniquement en prévisualisation équipe, pour repérer un contenu non publié. --}}
@if ($status instanceof \App\Domain\Content\Enums\ContentStatus && $status !== \App\Domain\Content\Enums\ContentStatus::Published && \App\Domain\Content\Support\ContentPreview::enabled())
    <x-kh.badge tone="warning" icon="triangle-alert">{{ $status->label() }} — non visible du public</x-kh.badge>
@endif
