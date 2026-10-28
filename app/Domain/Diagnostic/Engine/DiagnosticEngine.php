<?php

namespace App\Domain\Diagnostic\Engine;

/**
 * Point d'entrée du moteur : nettoie les réponses, applique les règles, complète les priorités
 * et calcule la maturité. Déterministe : mêmes réponses = même résultat (DIA-02).
 */
final class DiagnosticEngine
{
    public function __construct(
        private readonly VisibilityResolver $visibility = new VisibilityResolver,
        private readonly RuleEngine $rules = new RuleEngine,
        private readonly PainCompleter $painCompleter = new PainCompleter,
        private readonly MaturityScorer $maturity = new MaturityScorer,
    ) {}

    /**
     * @param  array<string, mixed>  $answers
     */
    public function run(TemplateDefinition $template, array $answers): DiagnosticOutcome
    {
        $answers = $this->visibility->prune($template, $answers);
        $result = $this->rules->evaluate($template->rules, $answers);
        $score = $this->maturity->score($answers);

        return new DiagnosticOutcome(
            templateVersion: $template->version,
            maturityScore: $score,
            maturityLevel: $this->maturity->level($score),
            pains: $this->painCompleter->complete($result['pains'], $answers),
            recommendations: $result['recommendations'],
            clarifications: $result['clarifications'],
        );
    }
}
