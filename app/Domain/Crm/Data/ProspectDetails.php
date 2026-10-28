<?php

namespace App\Domain\Crm\Data;

/**
 * Coordonnées saisies par un prospect (formulaire du brief), déjà validées.
 */
final readonly class ProspectDetails
{
    public function __construct(
        public string $fullName,
        public string $phone,
        public ?string $email = null,
        public ?string $company = null,
        public bool $marketingOptIn = false,
        public ?string $ip = null,
    ) {}
}
