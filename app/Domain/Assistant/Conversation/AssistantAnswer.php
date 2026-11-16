<?php

namespace App\Domain\Assistant\Conversation;

use App\Domain\Assistant\Enums\Grounding;
use App\Domain\Assistant\Enums\SuggestedAction;

/**
 * Réponse de l'assistant après validation serveur : c'est elle, et elle seule, qui est enregistrée et affichée.
 *
 * @phpstan-type Citation array{chunk_id: int, url: string, title: string}
 * @phpstan-type BriefDraft array{sector: string|null, company_size: string|null, current_tools: string|null, main_problem: string|null, desired_outcome: string|null, timeline: string|null, summary: string}
 */
final readonly class AssistantAnswer
{
    /**
     * @param  list<Citation>  $citations
     * @param  BriefDraft|null  $briefDraft
     */
    public function __construct(
        public string $answer,
        public Grounding $grounding,
        public array $citations,
        public SuggestedAction $suggestedAction,
        public ?string $clarifyingQuestion = null,
        public ?array $briefDraft = null,
        public ?string $errorCode = null,
    ) {}

    public static function fallback(string $text, string $errorCode, SuggestedAction $action = SuggestedAction::HandoffWhatsapp): self
    {
        return new self($text, Grounding::NoSource, [], $action, errorCode: $errorCode);
    }
}
