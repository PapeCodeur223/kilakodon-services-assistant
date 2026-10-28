<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Data\ProspectDetails;
use App\Domain\Crm\Data\UpsertedContact;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Support\DuplicateFinder;
use App\Domain\Crm\Support\PhoneNormalizer;
use InvalidArgumentException;

/**
 * Crée (ou met à jour, pour une nouvelle soumission du même brief) le contact du prospect.
 * Téléphone normalisé en E.164 ; un contact existant au même numéro ou e-mail est seulement
 * **suggéré** comme doublon : on ne rattache jamais une demande à une autre personne sans contrôle humain.
 */
class UpsertContact
{
    public function __construct(private readonly DuplicateFinder $duplicates) {}

    /**
     * @param  array{sector_code?: string|null, size_band?: string|null, sites_band?: string|null}  $companyProfile
     */
    public function handle(ProspectDetails $details, ?Contact $existing = null, array $companyProfile = []): UpsertedContact
    {
        $phone = PhoneNormalizer::toE164($details->phone)
            ?? throw new InvalidArgumentException('Numéro de téléphone invalide.');

        $contact = $existing ?? new Contact;
        $contact->fill([
            'full_name' => trim($details->fullName),
            'phone_e164' => $phone,
            'whatsapp_e164' => $phone,
            'email' => $details->email !== null && trim($details->email) !== '' ? mb_strtolower(trim($details->email)) : null,
        ]);

        $companyName = trim((string) $details->company);
        if ($companyName !== '') {
            $company = $contact->company_id !== null ? Company::query()->find($contact->company_id) : null;
            $company ??= new Company;
            $company->fill(['name' => $companyName, ...array_filter($companyProfile)])->save();
            $contact->company_id = $company->id;
        }

        $contact->save();

        return new UpsertedContact($contact, $this->duplicates->for($contact));
    }
}
