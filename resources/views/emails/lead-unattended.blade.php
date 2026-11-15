<x-mail::message>
# Leads en attente

{{ count($lead_ids ?? []) }} lead(s) attendent une première réponse depuis plus de 24 heures ouvrées :

@foreach ($lead_ids ?? [] as $id)
- [Lead #{{ $id }}]({{ url('/admin/leads/'.$id) }})
@endforeach

<x-mail::button :url="$adminUrl">
Ouvrir les leads
</x-mail::button>

Kilakodon Hub
</x-mail::message>
