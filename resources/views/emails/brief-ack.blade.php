<x-mail::message>
# Merci{{ !empty($first_name) ? ' '.$first_name : '' }}, votre brief est bien reçu

Votre référence : **{{ $reference ?? '' }}**. Gardez-la : elle nous permet de retrouver votre diagnostic et votre brief.

Un conseiller relit votre brief et revient vers vous pour en discuter. Pour votre sécurité, le lien privé de votre brief n'est pas recopié dans cet e-mail : utilisez celui affiché à l'écran, ou demandez-en un nouveau avec votre référence.

@if ($hours)
Nous vous répondons pendant nos horaires d'ouverture : {{ $hours }}.
@endif

<x-mail::button :url="$whatsapp" color="success">
Continuer sur WhatsApp
</x-mail::button>

Document de travail non contractuel.

Bien cordialement,<br>
L'équipe Kilakodon Services — Bamako
</x-mail::message>
