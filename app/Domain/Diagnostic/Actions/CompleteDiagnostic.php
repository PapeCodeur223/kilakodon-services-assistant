<?php

namespace App\Domain\Diagnostic\Actions;

use App\Domain\Diagnostic\Engine\DiagnosticEngine;
use App\Domain\Diagnostic\Engine\VisibilityResolver;
use App\Domain\Diagnostic\Enums\SessionStatus;
use App\Domain\Diagnostic\Jobs\GenerateDiagnosticSummary;
use App\Domain\Diagnostic\Models\DiagnosticResult;
use App\Domain\Diagnostic\Models\DiagnosticSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Calcule la restitution avec le moteur déterministe (DIA-02) et l'enregistre.
 * Le résumé IA est demandé ensuite, hors requête ; la restitution n'en dépend pas.
 */
class CompleteDiagnostic
{
    public function __construct(
        private readonly DiagnosticEngine $engine,
        private readonly VisibilityResolver $visibility,
    ) {}

    public function handle(DiagnosticSession $session): DiagnosticResult
    {
        $template = $session->loadMissing('template')->template->toDefinition();
        $answers = $this->visibility->prune($template, $session->answerMap());

        $missing = [];
        foreach ($template->steps() as $step) {
            $missing = [...$missing, ...$this->visibility->missingRequired($template, $answers, $step)];
        }
        if ($missing !== []) {
            throw ValidationException::withMessages(['answers' => 'Questions sans réponse : '.implode(', ', $missing)]);
        }

        $outcome = $this->engine->run($template, $answers);

        $result = DB::transaction(function () use ($session, $outcome): DiagnosticResult {
            $existing = DiagnosticResult::query()->where('session_id', $session->id)->first();
            $attributes = DiagnosticResult::attributesFrom($outcome);

            // Résultat identique : on garde le résumé déjà rédigé.
            if ($existing !== null && $existing->toOutcome()->toArray() === $outcome->toArray()) {
                $result = $existing;
            } else {
                $result = DiagnosticResult::query()->updateOrCreate(
                    ['session_id' => $session->id],
                    [...$attributes, 'ai_summary' => null, 'ai_summary_model' => null],
                );
            }

            if ($session->status !== SessionStatus::Converted) {
                $session->forceFill([
                    'status' => SessionStatus::Completed,
                    'completed_at' => $session->completed_at ?? now(),
                ])->save();
            }

            return $result;
        });

        if ($result->ai_summary === null) {
            GenerateDiagnosticSummary::dispatch($result->id)->afterCommit();
        }

        return $result;
    }
}
