<?php

namespace App\Domain\Brief\Data;

/**
 * Corrections du prospect avant validation : ce qu'il a dit (entreprise, précisions) et le résumé.
 * Les recommandations ne sont jamais modifiables (BRF-01).
 */
final readonly class BriefCorrections
{
    public function __construct(
        public ?string $company,
        public ?string $contextNote,
        public string $summary,
        public ?string $comment = null,
    ) {}
}
