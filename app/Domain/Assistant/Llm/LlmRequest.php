<?php

namespace App\Domain\Assistant\Llm;

/**
 * Requête au modèle. Le contenu utilisateur et les documents sont des données, jamais des instructions :
 * ils sont encadrés par l'appelant. `tool` force une sortie structurée (outil `respond`, TRD §7.3).
 */
final readonly class LlmRequest
{
    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>|null  $tool  définition d'outil {name, description, input_schema} imposée au modèle
     */
    public function __construct(
        public string $system,
        public array $messages,
        public int $maxTokens = 400,
        public string $purpose = 'generic',
        public ?array $tool = null,
    ) {}
}
