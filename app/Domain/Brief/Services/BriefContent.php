<?php

namespace App\Domain\Brief\Services;

use App\Domain\Crm\Models\Contact;
use App\Domain\Diagnostic\Engine\Catalog;
use App\Domain\Diagnostic\Engine\DiagnosticOutcome;
use App\Domain\Diagnostic\Engine\QuestionDefinition;
use App\Domain\Diagnostic\Engine\TemplateDefinition;

/**
 * Contenu figé d'une version de brief (BRF-01) :
 * - `declared` : ce que le prospect a dit (réponses avec leurs libellés du moment, coordonnées) ;
 * - `recommended` : copie des priorités, recommandations et clarifications calculées par les règles.
 *
 * @phpstan-type DeclaredAnswer array{key: string, question: string, answer: string, step: int}
 */
class BriefContent
{
    public const EDITABLE_ANSWER = 'context_note';

    /**
     * @param  array<string, mixed>  $answers  réponses nettoyées
     * @return array{contact: array{full_name: string, phone: string|null, email: string|null, company: string|null}, answers: list<DeclaredAnswer>, context_note: string|null}
     */
    public function declared(TemplateDefinition $template, array $answers, Contact $contact, ?string $company): array
    {
        $declared = [];

        foreach ($template->questions as $key => $question) {
            if ($key === self::EDITABLE_ANSWER || ! array_key_exists($key, $answers)) {
                continue;
            }

            $declared[] = [
                'key' => $key,
                'question' => $question->label,
                'answer' => $this->answerLabel($question, $answers[$key]),
                'step' => $question->step,
            ];
        }

        $note = $answers[self::EDITABLE_ANSWER] ?? null;

        return [
            'contact' => [
                'full_name' => $contact->full_name,
                'phone' => $contact->phone_e164,
                'email' => $contact->email,
                'company' => $company !== null && trim($company) !== '' ? trim($company) : null,
            ],
            'answers' => $declared,
            'context_note' => is_string($note) && trim($note) !== '' ? trim($note) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function recommended(DiagnosticOutcome $outcome): array
    {
        return [
            'template_version' => $outcome->templateVersion,
            'maturity' => [
                'score' => $outcome->maturityScore,
                'level' => $outcome->maturityLevel,
                'label' => $outcome->maturityLabel(),
                'explanation' => $outcome->maturityExplanation(),
            ],
            'pains' => $outcome->pains,
            'recommendations' => $outcome->recommendations,
            'clarifications' => $outcome->clarifications,
            'no_recommendation_message' => $outcome->needsConversation() ? Catalog::NO_RECOMMENDATION_MESSAGE : null,
        ];
    }

    private function answerLabel(QuestionDefinition $question, mixed $value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map(fn (mixed $v): string => $question->optionLabel((string) $v), $value));
        }

        return $question->hasOptions() || $value === Catalog::UNKNOWN
            ? $question->optionLabel((string) $value)
            : (string) $value;
    }
}
