<?php

namespace App\Domain\Diagnostic\Actions;

use App\Domain\Diagnostic\Engine\VisibilityResolver;
use App\Domain\Diagnostic\Enums\SessionStatus;
use App\Domain\Diagnostic\Models\DiagnosticSession;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Enregistre l'état du questionnaire à chaque étape (DIA-01, DIA-03).
 * Les réponses sont nettoyées par le moteur : une réponse devenue non pertinente est supprimée.
 */
class SaveDiagnosticAnswers
{
    public function __construct(private readonly VisibilityResolver $visibility) {}

    /**
     * @param  array<string, mixed>  $answers  état complet des réponses côté visiteur
     * @return array<string, mixed> réponses retenues
     */
    public function handle(DiagnosticSession $session, array $answers, int $currentStep, bool $savedForLater = false): array
    {
        if ($session->isExpired() || $session->status === SessionStatus::Converted) {
            throw new InvalidArgumentException('Cette session de diagnostic ne peut plus être modifiée.');
        }

        $template = $session->loadMissing('template')->template->toDefinition();
        $answers = $this->visibility->prune($template, $answers);
        $steps = $template->steps();
        $currentStep = max(min($currentStep, max($steps)), min($steps));

        DB::transaction(function () use ($session, $answers, $currentStep, $savedForLater): void {
            $session->answers()->whereNotIn('question_key', array_keys($answers) ?: [''])->delete();

            $existing = $session->answers()->pluck('value', 'question_key')->all();
            foreach ($answers as $key => $value) {
                if (! array_key_exists($key, $existing) || $existing[$key] !== $value) {
                    $session->answers()->updateOrCreate(['question_key' => $key], ['value' => $value]);
                }
            }

            $session->forceFill([
                'current_step' => $currentStep,
                // Une modification après la restitution impose un nouveau calcul.
                'status' => $savedForLater ? SessionStatus::Saved : SessionStatus::InProgress,
            ])->save();
        });

        $session->unsetRelation('answers');

        return $answers;
    }
}
