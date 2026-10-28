<x-mail::message>
# Demande de rendez-vous

Un prospect demande un rendez-vous (lead #{{ $lead_id ?? '?' }}). Les créneaux souhaités et les coordonnées sont dans la fiche.

Rappel : le rendez-vous reste « demandé » tant qu'un membre de l'équipe ne l'a pas confirmé dans l'admin.

<x-mail::button :url="$adminUrl">
Ouvrir le lead
</x-mail::button>

Kilakodon Hub
</x-mail::message>
