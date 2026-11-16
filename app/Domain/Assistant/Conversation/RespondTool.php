<?php

namespace App\Domain\Assistant\Conversation;

/**
 * Outil de sortie forcé `respond` (AI_ASSISTANT §3) : le modèle ne peut répondre que par cette structure.
 */
final class RespondTool
{
    public const NAME = 'respond';

    public const BRIEF_FIELDS = ['sector', 'company_size', 'current_tools', 'main_problem', 'desired_outcome', 'timeline'];

    /**
     * @return array{name: string, description: string, input_schema: array<string, mixed>}
     */
    public static function definition(): array
    {
        $nullableString = ['type' => ['string', 'null']];

        return [
            'name' => self::NAME,
            'description' => 'Réponse structurée de l\'Assistant Kilakodon.',
            'input_schema' => [
                'type' => 'object',
                'required' => ['answer', 'grounding', 'citations', 'suggested_action'],
                'properties' => [
                    'answer' => ['type' => 'string', 'maxLength' => 1500],
                    'grounding' => ['type' => 'string', 'enum' => ['sourced', 'partial', 'no_source']],
                    'citations' => ['type' => 'array', 'items' => ['type' => 'integer'], 'maxItems' => 4],
                    'suggested_action' => ['type' => 'string', 'enum' => ['none', 'start_diagnostic', 'propose_brief', 'request_appointment', 'handoff_whatsapp']],
                    'clarifying_question' => $nullableString,
                    'brief_draft' => [
                        'type' => ['object', 'null'],
                        'properties' => [
                            'sector' => $nullableString,
                            'company_size' => $nullableString,
                            'current_tools' => $nullableString,
                            'main_problem' => $nullableString,
                            'desired_outcome' => $nullableString,
                            'timeline' => $nullableString,
                            'summary' => ['type' => 'string', 'maxLength' => 600],
                        ],
                    ],
                ],
            ],
        ];
    }
}
