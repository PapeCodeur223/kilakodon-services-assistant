<?php

namespace App\Domain\Diagnostic\Engine;

/**
 * Résultat d'un diagnostic, sérialisé dans `diagnostic_results` (TRD §6).
 * C'est aussi la seule entrée autorisée pour le résumé IA.
 *
 * @phpstan-import-type Recommendation from RuleEngine
 * @phpstan-import-type Pain from RuleEngine
 * @phpstan-import-type Clarification from RuleEngine
 */
final readonly class DiagnosticOutcome
{
    /**
     * @param  list<Pain>  $pains
     * @param  list<Recommendation>  $recommendations
     * @param  list<Clarification>  $clarifications
     */
    public function __construct(
        public int $templateVersion,
        public float $maturityScore,
        public int $maturityLevel,
        public array $pains,
        public array $recommendations,
        public array $clarifications,
    ) {}

    public function maturityLabel(): string
    {
        return Catalog::MATURITY_LEVELS[$this->maturityLevel]['label'];
    }

    public function maturityExplanation(): string
    {
        return Catalog::MATURITY_LEVELS[$this->maturityLevel]['explanation'];
    }

    /**
     * Aucune recommandation : on ne l'invente pas, on propose un échange (T10).
     */
    public function needsConversation(): bool
    {
        return $this->recommendations === [];
    }

    /**
     * @return array{template_version: int, maturity_score: float, maturity_level: int, pains: list<Pain>, recommendations: list<Recommendation>, clarifications: list<Clarification>}
     */
    public function toArray(): array
    {
        return [
            'template_version' => $this->templateVersion,
            'maturity_score' => $this->maturityScore,
            'maturity_level' => $this->maturityLevel,
            'pains' => $this->pains,
            'recommendations' => $this->recommendations,
            'clarifications' => $this->clarifications,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        /** @var list<Pain> $pains */
        $pains = (array) ($data['pains'] ?? []);
        /** @var list<Recommendation> $recommendations */
        $recommendations = (array) ($data['recommendations'] ?? []);
        /** @var list<Clarification> $clarifications */
        $clarifications = (array) ($data['clarifications'] ?? []);

        return new self(
            (int) $data['template_version'],
            (float) $data['maturity_score'],
            (int) $data['maturity_level'],
            $pains,
            $recommendations,
            $clarifications,
        );
    }
}
