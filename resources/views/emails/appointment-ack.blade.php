<x-mail::message>
# Demande reçue{{ !empty($first_name) ? ', '.$first_name : '' }}

Nous avons bien reçu votre demande de rendez-vous{{ !empty($topic) ? ' : « '.$topic.' »' : '' }}.

**Ce rendez-vous n'est pas encore confirmé** : un conseiller vous contacte pour convenir du créneau, parmi ceux que vous avez proposés.

@if ($hours)
Nous vous répondons pendant nos horaires d'ouverture : {{ $hours }}.
@endif

<x-mail::button :url="$whatsapp" color="success">
Discuter sur WhatsApp
</x-mail::button>

Bien cordialement,<br>
L'équipe Kilakodon Services — Bamako
</x-mail::message>
