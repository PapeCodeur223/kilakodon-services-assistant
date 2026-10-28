<?php

use App\Domain\Diagnostic\Engine\ConditionEvaluator;
use App\Domain\Diagnostic\Engine\MaturityScorer;
use App\Domain\Diagnostic\Engine\PainCompleter;
use App\Domain\Diagnostic\Engine\VisibilityResolver;

$answers = [
    'sector' => 'retail',
    'pains' => ['stock_gaps', 'cash_control'],
    'budget_band' => '__unknown',
    'sites' => '2_3',
];

it('évalue chaque opérateur', function (array $leaf, bool $expected) use ($answers) {
    expect((new ConditionEvaluator)->passes($leaf, $answers))->toBe($expected);
})->with([
    'eq vrai' => [['q' => 'sector', 'op' => 'eq', 'v' => 'retail'], true],
    'eq faux' => [['q' => 'sector', 'op' => 'eq', 'v' => 'school'], false],
    'eq sur une liste' => [['q' => 'pains', 'op' => 'eq', 'v' => 'stock_gaps'], false],
    'neq' => [['q' => 'sector', 'op' => 'neq', 'v' => 'school'], true],
    'neq sans réponse' => [['q' => 'timeline', 'op' => 'neq', 'v' => 'asap'], true],
    'in' => [['q' => 'sites', 'op' => 'in', 'v' => ['2_3', '4_10']], true],
    'not_in' => [['q' => 'sites', 'op' => 'not_in', 'v' => ['2_3']], false],
    'contains' => [['q' => 'pains', 'op' => 'contains', 'v' => 'cash_control'], true],
    'contains absent' => [['q' => 'pains', 'op' => 'contains', 'v' => 'hr_payroll'], false],
    'contains sur réponse simple' => [['q' => 'sector', 'op' => 'contains', 'v' => 'retail'], true],
    'contains_any' => [['q' => 'pains', 'op' => 'contains_any', 'v' => ['hr_payroll', 'stock_gaps']], true],
    'contains_none' => [['q' => 'pains', 'op' => 'contains_none', 'v' => ['hr_payroll']], true],
    'contains_none faux' => [['q' => 'pains', 'op' => 'contains_none', 'v' => ['stock_gaps']], false],
    'contains_none sans réponse' => [['q' => 'current_tools', 'op' => 'contains_none', 'v' => ['erp']], true],
    'answered' => [['q' => 'sector', 'op' => 'answered'], true],
    'answered (je ne sais pas)' => [['q' => 'budget_band', 'op' => 'answered'], true],
    'not_answered' => [['q' => 'timeline', 'op' => 'not_answered'], true],
    'is_unknown' => [['q' => 'budget_band', 'op' => 'is_unknown'], true],
    'is_unknown faux' => [['q' => 'sector', 'op' => 'is_unknown'], false],
]);

it('combine all, any et none, avec imbrication', function () use ($answers) {
    $evaluator = new ConditionEvaluator;

    expect($evaluator->passes([], $answers))->toBeTrue()
        ->and($evaluator->passes(null, $answers))->toBeTrue()
        ->and($evaluator->passes(['all' => [['q' => 'sector', 'op' => 'eq', 'v' => 'retail']], 'any' => [['q' => 'sites', 'op' => 'eq', 'v' => '1'], ['q' => 'pains', 'op' => 'contains', 'v' => 'stock_gaps']]], $answers))->toBeTrue()
        ->and($evaluator->passes(['any' => [['q' => 'sites', 'op' => 'eq', 'v' => '1']]], $answers))->toBeFalse()
        ->and($evaluator->passes(['none' => [['q' => 'sector', 'op' => 'eq', 'v' => 'retail']]], $answers))->toBeFalse()
        ->and($evaluator->passes(['all' => [['any' => [['q' => 'sector', 'op' => 'eq', 'v' => 'school'], ['all' => [['q' => 'sites', 'op' => 'in', 'v' => ['2_3']]]]]]]], $answers))->toBeTrue();
});

it('trace les seules questions qui ont rendu la condition vraie', function () use ($answers) {
    $trace = (new ConditionEvaluator)->trace(['any' => [
        ['q' => 'sector', 'op' => 'eq', 'v' => 'school'],
        ['q' => 'pains', 'op' => 'contains', 'v' => 'stock_gaps'],
    ]], $answers);

    expect($trace)->toBe(['pains']);
});

it('refuse un opérateur ou un bloc inconnu', function () {
    (new ConditionEvaluator)->passes(['q' => 'sector', 'op' => 'like', 'v' => 'r'], []);
})->throws(InvalidArgumentException::class);

it('refuse un bloc de condition inconnu', function () {
    (new ConditionEvaluator)->passes(['some' => []], []);
})->throws(InvalidArgumentException::class);

it('calcule la maturité selon le barème (plafond 6, bonus de satisfaction)', function (array $tools, ?string $satisfaction, float $score, int $level) {
    $scorer = new MaturityScorer;
    $value = $scorer->score(array_filter(['current_tools' => $tools, 'existing_satisfaction' => $satisfaction]));

    expect($value)->toBe($score)->and($scorer->level($value))->toBe($level);
})->with([
    'rien' => [['none'], null, 0.0, 1],
    'papier et WhatsApp' => [['paper', 'whatsapp'], null, 0.5, 1],
    'Excel seul' => [['excel'], null, 1.0, 1],
    'Excel et WhatsApp' => [['excel', 'whatsapp'], null, 1.5, 2],
    'caisse et Excel' => [['pos_software', 'excel'], null, 3.0, 2],
    'caisse satisfaite' => [['pos_software', 'excel'], 'ok', 4.0, 3],
    'ERP, compta, sur mesure' => [['erp', 'accounting_software', 'custom_app'], null, 6.0, 4],
    'plafond puis bonus' => [['erp', 'accounting_software', 'custom_app', 'excel'], 'ok', 7.0, 4],
]);

it('complète les priorités par l\'objectif principal', function () {
    $completer = new PainCompleter;

    expect(array_column($completer->complete([], ['main_goal' => 'control']), 'key'))->toBe(['stock_gaps'])
        ->and(array_column($completer->complete([PainCompleter::pain('stock_gaps', ['r_stock_pain'], 'rule')], ['main_goal' => 'control']), 'key'))->toBe(['stock_gaps', 'cash_control'])
        ->and(array_column($completer->complete([PainCompleter::pain('customer_followup', [], 'rule')], ['main_goal' => 'growth']), 'key'))->toBe(['customer_followup'])
        ->and($completer->complete([], []))->toBe([]);
});

it('n\'affiche les options scolaires qu\'aux écoles et applique les choix exclusifs', function () {
    $resolver = new VisibilityResolver;
    $pains = diagnosticV1()->question('pains');

    $values = fn (string $sector): array => array_column($resolver->visibleOptions($pains, ['sector' => $sector]), 'value');

    expect($values('school'))->toContain('school_fees', 'school_grades')->not->toContain('production_waste')
        ->and($values('retail'))->not->toContain('school_fees')
        ->and($values('bakery_food'))->toContain('production_waste');

    $pruned = $resolver->prune(diagnosticV1(), diagnosticAnswers(['current_tools' => ['excel', 'none'], 'pains' => ['__unknown']]));

    expect($pruned['current_tools'])->toBe(['none'])
        ->and($pruned['pains'])->toBe('__unknown');
});

it('signale les questions requises visibles sans réponse', function () {
    $resolver = new VisibilityResolver;

    expect($resolver->missingRequired(diagnosticV1(), ['sector' => 'school'], 1))->toBe(['team_size', 'sites', 'school_size'])
        ->and($resolver->missingRequired(diagnosticV1(), ['sector' => 'retail'], 1))->toBe(['team_size', 'sites'])
        ->and($resolver->missingRequired(diagnosticV1(), [], 4))->toBe(['timeline', 'decision_role']);
});

it('écarte une réponse « Je ne sais pas » non autorisée et tronque le texte libre', function () {
    $pruned = (new VisibilityResolver)->prune(diagnosticV1(), diagnosticAnswers([
        'sector' => '__unknown',
        'context_note' => str_repeat('a', 600),
    ]));

    expect($pruned)->not->toHaveKey('sector')
        ->and(mb_strlen($pruned['context_note']))->toBe(500);
});
