<?php

namespace App\Domain\Assistant\Llm;

/**
 * Réponse du modèle : texte libre, ou arguments de l'outil imposé (`toolInput`), à valider côté serveur.
 */
final readonly class LlmResponse
{
    /**
     * @param  array<string, mixed>|null  $toolInput
     */
    public function __construct(
        public string $text,
        public string $model,
        public int $inputTokens = 0,
        public int $outputTokens = 0,
        public ?array $toolInput = null,
    ) {}
}
