<?php

namespace App\Domain\Assistant\Llm;

/**
 * Le contenu utilisateur est une donnée, jamais une instruction : il est encadré par l'appelant.
 */
final readonly class LlmRequest
{
    /**
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function __construct(
        public string $system,
        public array $messages,
        public int $maxTokens = 400,
        public string $purpose = 'generic',
    ) {}
}
