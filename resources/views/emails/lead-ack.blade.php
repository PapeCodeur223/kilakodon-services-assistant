<x-mail::message>
# Merci{{ !empty($first_name) ? ' '.$first_name : '' }} !

Votre message est bien arrivé. Un conseiller l'étudie et revient vers vous pour comprendre votre besoin.

@if ($hours)
Nous vous répondons pendant nos horaires d'ouverture : {{ $hours }}.
@endif

Le plus rapide pour échanger : WhatsApp.

<x-mail::button :url="$whatsapp" color="success">
Discuter sur WhatsApp
</x-mail::button>

Bien cordialement,<br>
L'équipe Kilakodon Services — Bamako
</x-mail::message>
