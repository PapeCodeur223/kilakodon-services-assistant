<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\ConsentPurpose;
use App\Domain\Crm\Enums\ConsentSource;
use App\Domain\Crm\Models\Contact;

/**
 * Consentements d'un formulaire : traitement de la demande (obligatoire) et marketing (séparé,
 * jamais pré-coché), avec la version du texte affiché (PRV-01). Écriture seule.
 */
class RecordConsents
{
    public function __construct(private readonly RecordConsent $consent) {}

    public function handle(Contact $contact, bool $marketing, ConsentSource $source, ?string $ip): void
    {
        $this->consent->handle($contact, ConsentPurpose::RequestProcessing, true, $source, $ip);
        $this->consent->handle($contact, ConsentPurpose::Marketing, $marketing, $source, $ip);
    }
}
