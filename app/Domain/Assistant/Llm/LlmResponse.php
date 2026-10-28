<?php

namespace App\Domain\Assistant\Llm;

final readonly class LlmResponse
{
    public function __construct(
        public string $text,
        public string $model,
        public int $inputTokens = 0,
        public int $outputTokens = 0,
    ) {}
}
