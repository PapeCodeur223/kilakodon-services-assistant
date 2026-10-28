<?php

namespace App\Domain\Crm\Data;

use App\Domain\Crm\Models\Contact;
use Illuminate\Support\Collection;

final readonly class UpsertedContact
{
    /**
     * @param  Collection<int, Contact>  $duplicates  contacts existants partageant un téléphone ou un e-mail
     */
    public function __construct(
        public Contact $contact,
        public Collection $duplicates,
    ) {}
}
