<?php

namespace App\Domain\Diagnostic\Actions;

use App\Domain\Diagnostic\Models\DiagnosticQuestion;
use App\Domain\Diagnostic\Models\DiagnosticRule;
use App\Domain\Diagnostic\Models\DiagnosticTemplate;
use App\Domain\Platform\Actions\RecordAudit;
use App\Domain\Platform\Enums\AuditAction;

/**
 * « Dupliquer en nouvelle version » : seule façon de faire évoluer un template verrouillé.
 */
class DuplicateDiagnosticTemplate
{
    public function __construct(
        private readonly CreateDiagnosticTemplate $create,
        private readonly RecordAudit $audit,
    ) {}

    public function handle(DiagnosticTemplate $source): DiagnosticTemplate
    {
        $source->loadMissing(['questions', 'rules']);
        $version = (int) DiagnosticTemplate::query()->max('version') + 1;

        $copy = $this->create->handle([
            'name' => preg_replace('/v\d+$/', "v{$version}", $source->name) ?? $source->name,
            'version' => $version,
            'notes' => "Copie de la version {$source->version}.",
            'questions' => $source->questions->map(fn (DiagnosticQuestion $q): array => $q->toDefinitionArray())->values()->all(),
            'rules' => $source->rules->map(fn (DiagnosticRule $r): array => $r->toDefinitionArray())->values()->all(),
        ]);

        $this->audit->handle(AuditAction::TemplateDuplicated, $copy, ['before' => ['version' => $source->version], 'after' => ['version' => $version]]);

        return $copy;
    }
}
