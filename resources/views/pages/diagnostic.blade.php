{{-- Hôte du questionnaire : mise en page focalisée, sans pied de page (UX §5.4). --}}
<x-layouts.public
    title="Diagnostic gratuit de votre gestion"
    description="En 3 minutes, sans inscription : votre niveau d'équipement numérique, vos 3 priorités et les modules de gestion recommandés pour votre entreprise au Mali."
    :noindex="$session !== null"
    chrome="none"
>
    <x-kh.focus-header />
    <div class="mx-auto max-w-[720px] px-4 pt-8 sm:px-6 sm:pt-12">
        <livewire:diagnostic.wizard :session="$session" :token="$token" :entry="$entry" :sector="$sector" />
    </div>
</x-layouts.public>
