<x-mail::message>
# Votre rendez-vous est confirmé

Bonjour{{ !empty($first_name) ? ' '.$first_name : '' }},

Nous vous confirmons notre rendez-vous :

- **Date** : {{ $date ?? '' }}
- **Heure** : {{ $time ?? '' }} (heure de Bamako)
- **Format** : {{ $channel ?? '' }}
@if (!empty($topic))
- **Sujet** : {{ $topic }}
@endif

Un empêchement ? Prévenez-nous simplement sur WhatsApp.

<x-mail::button :url="$whatsapp" color="success">
Nous écrire sur WhatsApp
</x-mail::button>

Bien cordialement,<br>
L'équipe Kilakodon Services — Bamako
</x-mail::message>
