<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Brief\Models\Brief;
use App\Domain\Crm\Enums\ActivityType;
use App\Domain\Crm\Exceptions\CrmException;
use App\Domain\Crm\Models\Consent;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Models\PrivacyRequest;
use App\Domain\Platform\Actions\RecordAudit;
use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Fusion manuelle de deux contacts (CRM-01) : jamais automatique, toujours journalisée.
 * Leads, briefs, consentements et demandes de droits passent sur le contact conservé ;
 * ses champs vides sont complétés ; le doublon est marqué fusionné puis archivé.
 */
class MergeContacts
{
    public function __construct(private readonly RecordAudit $audit) {}

    public function handle(Contact $duplicate, Contact $kept, User $by): Contact
    {
        if ($duplicate->is($kept)) {
            throw new CrmException('Impossible de fusionner un contact avec lui-même.');
        }
        if ($duplicate->merged_into_id !== null || $kept->merged_into_id !== null) {
            throw new CrmException('L\'un de ces contacts a déjà été fusionné.');
        }
        if ($duplicate->anonymized_at !== null || $kept->anonymized_at !== null) {
            throw new CrmException('Un contact anonymisé ne peut pas être fusionné.');
        }

        return DB::transaction(function () use ($duplicate, $kept, $by): Contact {
            $leadIds = Lead::query()->where('contact_id', $duplicate->id)->pluck('id');

            Lead::query()->whereKey($leadIds)->update(['contact_id' => $kept->id]);
            Brief::query()->where('contact_id', $duplicate->id)->update(['contact_id' => $kept->id]);
            Consent::query()->where('contact_id', $duplicate->id)->toBase()->update(['contact_id' => $kept->id]);
            PrivacyRequest::query()->where('contact_id', $duplicate->id)->update(['contact_id' => $kept->id]);

            foreach (['phone_e164', 'whatsapp_e164', 'email', 'job_title', 'city', 'company_id'] as $field) {
                if ($kept->getAttribute($field) === null && $duplicate->getAttribute($field) !== null) {
                    $kept->setAttribute($field, $duplicate->getAttribute($field));
                }
            }
            $kept->marketing_opt_in = $kept->marketing_opt_in || $duplicate->marketing_opt_in;
            $kept->save();

            $duplicate->forceFill(['merged_into_id' => $kept->id])->save();
            $duplicate->delete();

            foreach (Lead::query()->whereKey($leadIds)->get() as $lead) {
                $lead->activities()->create([
                    'user_id' => $by->id,
                    'type' => ActivityType::System,
                    'body' => "Contact fusionné : #{$duplicate->id} → #{$kept->id}",
                ]);
            }

            $this->audit->handle(AuditAction::ContactMerged, $kept, [
                'before' => ['duplicate_id' => $duplicate->id],
                'after' => ['kept_id' => $kept->id, 'moved_leads' => $leadIds->all()],
            ], $by);

            return $kept;
        });
    }
}
