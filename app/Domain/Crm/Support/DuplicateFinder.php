<?php

namespace App\Domain\Crm\Support;

use App\Domain\Crm\Models\Contact;
use Illuminate\Support\Collection;

/**
 * Doublons possibles (même téléphone ou même e-mail) — une **suggestion** pour l'équipe,
 * jamais une fusion automatique (P4 ; la fusion arrive au lot 3).
 */
class DuplicateFinder
{
    /**
     * @return Collection<int, Contact>
     */
    public function for(Contact $contact): Collection
    {
        $phones = array_values(array_filter([$contact->phone_e164, $contact->whatsapp_e164]));

        if ($phones === [] && $contact->email === null) {
            return new Collection;
        }

        return Contact::query()
            ->whereKeyNot($contact->getKey())
            ->whereNull('merged_into_id')
            ->whereNull('anonymized_at')
            ->where(function ($query) use ($phones, $contact): void {
                if ($phones !== []) {
                    $query->whereIn('phone_e164', $phones)->orWhereIn('whatsapp_e164', $phones);
                }
                if ($contact->email !== null) {
                    $query->orWhere('email', $contact->email);
                }
            })
            ->orderBy('id')
            ->get();
    }
}
