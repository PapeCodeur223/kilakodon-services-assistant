<?php

namespace App\Domain\Diagnostic\Actions;

use App\Domain\Diagnostic\Enums\TemplateStatus;
use App\Domain\Diagnostic\Models\DiagnosticTemplate;
use Illuminate\Support\Facades\DB;

/**
 * Crée un template en brouillon depuis une définition (fichier seedé, duplication).
 */
class CreateDiagnosticTemplate
{
    /**
     * @param  array{name: string, version: int, notes?: string|null, questions: list<array<string, mixed>>, rules: list<array<string, mixed>>}  $definition
     */
    public function handle(array $definition): DiagnosticTemplate
    {
        return DB::transaction(function () use ($definition): DiagnosticTemplate {
            $template = DiagnosticTemplate::query()->create([
                'name' => $definition['name'],
                'version' => $definition['version'],
                'status' => TemplateStatus::Draft,
                'notes' => $definition['notes'] ?? null,
            ]);

            foreach ($definition['questions'] as $i => $question) {
                $template->questions()->create([
                    'key' => $question['key'],
                    'step' => $question['step'],
                    'label' => $question['label'],
                    'help' => $question['help'] ?? null,
                    'type' => $question['type'],
                    'options' => $question['options'] ?? null,
                    'allow_unknown' => (bool) ($question['allow_unknown'] ?? false),
                    'required' => (bool) ($question['required'] ?? false),
                    'max_selections' => $question['max_selections'] ?? null,
                    'show_if' => $question['show_if'] ?? null,
                    'sort' => $question['sort'] ?? $i,
                ]);
            }

            foreach ($definition['rules'] as $rule) {
                $template->rules()->create([
                    'key' => $rule['key'],
                    'name' => $rule['name'] ?? $rule['key'],
                    'conditions' => $rule['conditions'],
                    'effects' => $rule['effects'],
                    'explanation' => $rule['explanation'] ?? '',
                    'priority' => $rule['priority'],
                    'is_active' => (bool) ($rule['is_active'] ?? true),
                ]);
            }

            return $template;
        });
    }
}
