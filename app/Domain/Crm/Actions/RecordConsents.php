<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\ConsentPurpose;
use App\Domain\Crm\Enums\ConsentSource;
use App\Domain\Crm\Models\Consent;
use App\Domain\Crm\Models\Contact;
use App\Domain\Platform\Services\Settings;
use App\Domain\Platform\Support\IpHasher;

/**
 * Consentements d'un formulaire : traitement de la demande (obligatoire) et marketing (séparé,
 * jamais pré-coché), avec la version du texte affiché (PRV-01). Écriture seule.
 */
class RecordConsents
{
    public function __construct(
        private readonly Settings $settings,
        private readonly IpHasher $ipHasher,
    ) {}

    public function handle(Contact $contact, bool $marketing, ConsentSource $source, ?string $ip): void
    {
        $version = (string) (((array) $this->settings->get('consent.texts', []))['version'] ?? 1);
        $ipHash = $ip !== null ? $this->ipHasher->hash($ip) : null;

        foreach ([ConsentPurpose::RequestProcessing->value => true, ConsentPurpose::Marketing->value => $marketing] as $purpose => $granted) {
            Consent::query()->create([
                'contact_id' => $contact->id,
                'purpose' => $purpose,
                'granted' => $granted,
                'text_version' => $version,
                'source' => $source,
                'ip_hash' => $ipHash,
            ]);
        }

        if ($contact->marketing_opt_in !== $marketing) {
            $contact->forceFill(['marketing_opt_in' => $marketing])->save();
        }
    }
}
