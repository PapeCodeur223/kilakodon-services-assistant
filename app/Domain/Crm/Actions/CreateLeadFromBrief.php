<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Brief\Models\Brief;
use App\Domain\Crm\Enums\LeadType;
use App\Domain\Crm\Models\Lead;
use App\Domain\Diagnostic\Engine\Catalog;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Lead issu d'un brief (P1, étape 8). Idempotent : un brief ne produit qu'un lead
 * (`leads.brief_id` unique, clé d'idempotence dérivée du brief).
 */
class CreateLeadFromBrief
{
    public function __construct(private readonly QueueLeadNotifications $notifications) {}

    public function handle(Brief $brief): Lead
    {
        $existing = Lead::query()->where('brief_id', $brief->id)->first();
        if ($existing !== null) {
            return $existing;
        }

        $brief->loadMissing(['contact', 'diagnosticSession.answers', 'latestVersion']);
        $contact = $brief->contact ?? throw new InvalidArgumentException('Le brief n\'a pas de contact.');

        try {
            return DB::transaction(function () use ($brief, $contact): Lead {
                $session = $brief->diagnosticSession;
                $answers = $session?->answerMap() ?? [];
                $answer = fn (string $key): ?string => isset($answers[$key]) && is_string($answers[$key]) && $answers[$key] !== Catalog::UNKNOWN
                    ? mb_substr($answers[$key], 0, 16)
                    : null;
                $recommended = $brief->latestVersion !== null ? $brief->latestVersion->recommended : [];

                $lead = Lead::query()->create([
                    'contact_id' => $contact->id,
                    'company_id' => $contact->company_id,
                    'brief_id' => $brief->id,
                    'type' => LeadType::Diagnostic,
                    'source' => mb_substr($session?->utm['utm_source'] ?? $session->entry ?? 'diagnostic', 0, 64),
                    'message' => "Brief {$brief->reference} — ".$brief->latestVersion?->summary,
                    'maturity_level' => $recommended['maturity']['level'] ?? null,
                    'budget_band' => $answer('budget_band'),
                    'timeline' => $answer('timeline'),
                    'decision_role' => $answer('decision_role'),
                    'idempotency_key' => $this->idempotencyKey($brief),
                    'utm' => $session?->utm,
                ]);

                $this->notifications->handle($lead, $contact, 'brief_ack', ['reference' => $brief->reference]);

                return $lead;
            });
        } catch (UniqueConstraintViolationException) {
            return Lead::query()->where('brief_id', $brief->id)->firstOrFail();
        }
    }

    /**
     * BACKEND_SCHEMA §8.2 : dérivée du type et du brief (un brief = un lead, quel que soit le jour).
     */
    public function idempotencyKey(Brief $brief): string
    {
        return hash('sha256', LeadType::Diagnostic->value.'|brief:'.$brief->id);
    }
}
