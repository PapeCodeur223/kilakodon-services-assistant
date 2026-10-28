<?php

namespace App\Domain\Diagnostic\Actions;

use App\Domain\Diagnostic\Enums\TemplateStatus;
use App\Domain\Diagnostic\Models\DiagnosticTemplate;
use App\Domain\Platform\Actions\RecordAudit;
use App\Domain\Platform\Enums\AuditAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Active un brouillon et retire l'actif précédent : un seul template `active` (BACKEND_SCHEMA §4).
 * Le template est validé avant (opérateurs, questions citées, modules connus) puis devient immuable.
 * Les sessions déjà commencées gardent leur version (DIA-03).
 */
class ActivateDiagnosticTemplate
{
    public function __construct(private readonly RecordAudit $audit) {}

    public function handle(DiagnosticTemplate $template): DiagnosticTemplate
    {
        if ($template->status !== TemplateStatus::Draft) {
            throw ValidationException::withMessages(['status' => 'Seul un brouillon peut être activé.']);
        }

        $errors = $template->toDefinition()->validate();
        if ($errors !== []) {
            throw ValidationException::withMessages(['template' => $errors]);
        }

        return DB::transaction(function () use ($template): DiagnosticTemplate {
            $previous = DiagnosticTemplate::query()->active()->lockForUpdate()->get();

            foreach ($previous as $active) {
                $active->update(['status' => TemplateStatus::Retired]);
            }

            $template->update(['status' => TemplateStatus::Active, 'activated_at' => now()]);

            $this->audit->handle(AuditAction::TemplateActivated, $template, [
                'after' => ['version' => $template->version],
                'before' => ['retired_versions' => $previous->pluck('version')->all()],
            ]);

            return $template;
        });
    }
}
