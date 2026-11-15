<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\ConsentPurpose;
use App\Domain\Crm\Enums\ConsentSource;
use App\Domain\Crm\Models\Consent;
use App\Domain\Crm\Models\Contact;
use App\Domain\Platform\Services\Settings;
use App\Domain\Platform\Support\IpHasher;

/**
 * Un consentement (PRV-01) : accord ou retrait, avec la version du texte affiché. Écriture seule :
 * un retrait crée une nouvelle ligne `granted = false`. Le marketing est recopié sur le contact.
 */
class RecordConsent
{
    public function __construct(
        private readonly Settings $settings,
        private readonly IpHasher $ipHasher,
    ) {}

    public function handle(?Contact $contact, ConsentPurpose $purpose, bool $granted, ConsentSource $source, ?string $ip = null, ?string $visitorKey = null): Consent
    {
        $consent = Consent::query()->create([
            'contact_id' => $contact?->id,
            'visitor_key' => $visitorKey,
            'purpose' => $purpose,
            'granted' => $granted,
            'text_version' => (string) (((array) $this->settings->get('consent.texts', []))['version'] ?? 1),
            'source' => $source,
            'ip_hash' => $ip !== null ? $this->ipHasher->hash($ip) : null,
        ]);

        if ($contact !== null && $purpose === ConsentPurpose::Marketing && $contact->marketing_opt_in !== $granted) {
            $contact->forceFill(['marketing_opt_in' => $granted])->save();
        }

        return $consent;
    }
}
