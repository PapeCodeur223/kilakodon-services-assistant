{{-- Brief privé : présentation « document » (UX §5.5). --}}
<x-layouts.public :title="'Brief '.$brief->reference" description="Votre brief Kilakodon Services : relisez-le, corrigez-le et validez-le." noindex chrome="none">
    <x-kh.focus-header exit-label="Fermer" />
    <div class="min-h-dvh bg-fog px-4 py-8 sm:px-6 sm:py-12">
        <livewire:brief.show :brief="$brief" :token="$token" />
    </div>
</x-layouts.public>
