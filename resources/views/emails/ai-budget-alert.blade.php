<x-mail::message>
# Budget de l'assistant IA

L'assistant a consommé **{{ $percent ?? '?' }} %** de son budget du {{ $date ?? '' }} ({{ $spent_usd ?? '?' }} $ sur {{ $budget_usd ?? '?' }} $).

À 100 %, il se coupe automatiquement jusqu'au lendemain et le site affiche le message de repli (diagnostic, WhatsApp, contact). Vous pouvez ajuster le budget ou couper l'assistant dans les paramètres.

<x-mail::button :url="$adminUrl">
Ouvrir les paramètres
</x-mail::button>

Kilakodon Hub
</x-mail::message>
