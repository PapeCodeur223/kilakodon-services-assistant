<?php

namespace Database\Seeders;

use App\Domain\Diagnostic\Actions\ActivateDiagnosticTemplate;
use App\Domain\Diagnostic\Actions\CreateDiagnosticTemplate;
use App\Domain\Diagnostic\Models\DiagnosticTemplate;
use Illuminate\Database\Seeder;

/**
 * Questionnaire v1 depuis docs/DIAGNOSTIC_SPEC.md (database/seeders/content/diagnostic-v1.php),
 * créé puis activé. Idempotent : une version déjà présente n'est jamais modifiée.
 */
class DiagnosticTemplateV1Seeder extends Seeder
{
    public function run(CreateDiagnosticTemplate $create, ActivateDiagnosticTemplate $activate): void
    {
        $definition = require database_path('seeders/content/diagnostic-v1.php');

        if (DiagnosticTemplate::query()->where('version', $definition['version'])->exists()) {
            return;
        }

        $template = $create->handle($definition);

        if (! DiagnosticTemplate::query()->active()->exists()) {
            $activate->handle($template);
        }
    }
}
