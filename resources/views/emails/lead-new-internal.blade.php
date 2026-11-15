<x-mail::message>
# Nouveau lead

Un nouveau lead **{{ \App\Domain\Crm\Enums\LeadType::tryFrom((string) ($type ?? ''))?->label() ?? 'reçu' }}** vient d'arriver{{ isset($reference) ? ' (brief '.$reference.')' : '' }}.

Par sécurité, cet e-mail ne contient aucune coordonnée : ouvrez la fiche pour le traiter.

<x-mail::button :url="$adminUrl">
Ouvrir le lead
</x-mail::button>

Kilakodon Hub
</x-mail::message>
