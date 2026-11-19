<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\PrivacyRequestStatus;
use App\Domain\Crm\Exceptions\CrmException;
use App\Domain\Crm\Models\PrivacyRequest;
use App\Domain\Platform\Actions\RecordAudit;
use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\User;

/**
 * Étapes de traitement d'une demande de droits (PRV-02) : identité vérifiée, traitée ou rejetée.
 * Une demande close ne se rouvre pas ; un rejet exige un motif. Chaque étape est journalisée.
 */
class ProcessPrivacyRequest
{
    public function __construct(private readonly RecordAudit $audit) {}

    public function verify(PrivacyRequest $request, User $by): PrivacyRequest
    {
        $this->ensureOpen($request);

        return $this->apply($request, $by, PrivacyRequestStatus::InProgress, ['verified_at' => $request->verified_at ?? now()]);
    }

    public function close(PrivacyRequest $request, User $by, PrivacyRequestStatus $to, ?string $note): PrivacyRequest
    {
        $this->ensureOpen($request);
        $note = $note !== null && trim($note) !== '' ? mb_substr(trim($note), 0, 2000) : null;

        if (! in_array($to, [PrivacyRequestStatus::Done, PrivacyRequestStatus::Rejected], true)) {
            throw new CrmException('Clôture possible seulement en « traitée » ou « rejetée ».');
        }
        if ($to === PrivacyRequestStatus::Rejected && $note === null) {
            throw new CrmException('Indiquez le motif du rejet.');
        }
        if ($to === PrivacyRequestStatus::Done && $request->verified_at === null) {
            throw new CrmException('Vérifiez d’abord l’identité du demandeur.');
        }

        return $this->apply($request, $by, $to, [
            'completed_at' => now(),
            'decision_note' => $note ?? $request->decision_note,
        ]);
    }

    /**
     * Garde avant export ou anonymisation : demande ouverte, identité vérifiée, contact relié non anonymisé.
     */
    public function ensureProcessable(PrivacyRequest $request): void
    {
        $this->ensureOpen($request);

        if ($request->verified_at === null) {
            throw new CrmException('Vérifiez d’abord l’identité du demandeur.');
        }
        if ($request->contact === null || $request->contact->anonymized_at !== null) {
            throw new CrmException('Reliez d’abord le contact concerné.');
        }
    }

    /**
     * Le contact relié change : la vérification d'identité portait sur l'ancien, elle est annulée.
     */
    public function contactChanged(PrivacyRequest $request, ?int $newContactId, User $by): bool
    {
        if ($request->verified_at === null || $request->contact_id === $newContactId) {
            return false;
        }

        $this->apply($request, $by, PrivacyRequestStatus::Verifying, ['verified_at' => null, 'contact_id' => $newContactId]);

        return true;
    }

    private function ensureOpen(PrivacyRequest $request): void
    {
        if (in_array($request->status, [PrivacyRequestStatus::Done, PrivacyRequestStatus::Rejected], true)) {
            throw new CrmException('Cette demande est déjà close.');
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function apply(PrivacyRequest $request, User $by, PrivacyRequestStatus $to, array $attributes): PrivacyRequest
    {
        $from = $request->status;

        $request->forceFill([...$attributes, 'status' => $to, 'handled_by' => $by->id])->save();

        $this->audit->handle(AuditAction::StatusChanged, $request, [
            'before' => ['status' => $from->value],
            'after' => ['status' => $to->value],
        ], $by);

        return $request;
    }
}
