<?php

namespace App\Domain\Diagnostic\Data;

/**
 * Contexte d'entrée dans le diagnostic (page d'origine, pré-remplissage, UTM).
 */
final readonly class StartDiagnostic
{
    /**
     * @param  array<string, string>  $utm
     */
    public function __construct(
        public string $ip,
        public ?string $entry = null,
        public ?string $sector = null,
        public ?string $visitorKey = null,
        public array $utm = [],
    ) {}
}
