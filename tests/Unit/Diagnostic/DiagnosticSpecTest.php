<?php

use App\Domain\Diagnostic\Engine\Catalog;
use App\Domain\Diagnostic\Engine\DiagnosticEngine;
use App\Domain\Diagnostic\Engine\DiagnosticOutcome;
use App\Domain\Diagnostic\Engine\VisibilityResolver;

/**
 * Cas de test obligatoires — DIAGNOSTIC_SPEC §6.
 */
function runDiagnostic(array $answers): DiagnosticOutcome
{
    return (new DiagnosticEngine)->run(diagnosticV1(), $answers);
}

/**
 * @return array<string, int> module => poids
 */
function modules(DiagnosticOutcome $outcome): array
{
    return array_column($outcome->recommendations, 'weight', 'module');
}

it('T1 — commerce multi-sites, stock et caisse', function () {
    $outcome = runDiagnostic(diagnosticAnswers([
        'sector' => 'retail', 'team_size' => '6_20', 'sites' => '2_3',
        'current_tools' => ['excel', 'whatsapp'], 'pains' => ['stock_gaps', 'cash_control'],
        'connectivity' => 'unstable', 'mobile_money' => 'wanted', 'main_goal' => 'time',
    ]));

    expect(modules($outcome))->toBe(['MOD_STOCK_POS' => 8, 'MOD_MULTISITE' => 3, 'MOD_OFFLINE' => 3, 'MOD_MOBILE_MONEY' => 3])
        ->and($outcome->maturityScore)->toBe(1.5)
        ->and($outcome->maturityLevel)->toBe(2)
        ->and(array_column($outcome->pains, 'key'))->toBe(['stock_gaps', 'cash_control', 'management_reports'])
        ->and($outcome->pains[2]['source'])->toBe('goal');
});

it('T2 — école de taille moyenne au papier et à Excel', function () {
    $outcome = runDiagnostic(diagnosticAnswers([
        'sector' => 'school', 'school_size' => '300_1000', 'team_size' => '21_50', 'sites' => '1',
        'current_tools' => ['paper', 'excel'], 'pains' => ['school_fees', 'school_grades'],
    ]));

    expect(modules($outcome))->toBe(['MOD_SCHOOL' => 6, 'MOD_ERP_CORE' => 3])
        ->and(array_column($outcome->clarifications, 'key'))->toContain('data_migration');

    $erp = collect($outcome->recommendations)->firstWhere('module', 'MOD_ERP_CORE');
    expect(array_column($erp['reasons'], 'rule_key'))->toBe(['r_erp_paper_big']);
});

it('T3 — boulangerie multi-sites : le stock reste sous le seuil', function () {
    $outcome = runDiagnostic(diagnosticAnswers([
        'sector' => 'bakery_food', 'team_size' => '6_20', 'sites' => '4_10', 'pains' => ['production_waste'],
    ]));

    expect(modules($outcome))->toBe(['MOD_BAKERY' => 5, 'MOD_MULTISITE' => 3])
        ->and(modules($outcome))->not->toHaveKey('MOD_STOCK_POS');
});

it('T4 — logistique avec un ERP insatisfaisant', function () {
    $outcome = runDiagnostic(diagnosticAnswers([
        'sector' => 'logistics', 'team_size' => '6_20', 'current_tools' => ['erp'], 'existing_satisfaction' => 'no',
    ]));

    expect(modules($outcome))->toEqualCanonicalizing(['MOD_WEB_APP' => 3, 'MOD_AUDIT' => 3])
        ->and($outcome->maturityScore)->toBe(3.0)
        ->and($outcome->maturityLevel)->toBe(2)
        ->and(array_column($outcome->clarifications, 'key'))->toContain('process_mapping', 'data_migration');
});

it('T5 — petite structure sans outil qui veut soigner son image', function () {
    $outcome = runDiagnostic(diagnosticAnswers([
        'current_tools' => ['none'], 'pains' => ['online_presence'], 'main_goal' => 'image',
    ]));

    expect(modules($outcome))->toBe(['MOD_DESIGN' => 3])
        ->and($outcome->maturityScore)->toBe(0.0)
        ->and($outcome->maturityLevel)->toBe(1);
});

it('T6 — seul le suivi client est signalé', function () {
    $outcome = runDiagnostic(diagnosticAnswers([
        'current_tools' => ['paper'], 'pains' => ['customer_followup'], 'main_goal' => 'time',
    ]));

    expect(modules($outcome))->toBe(['MOD_CRM' => 3]);
});

it('T7 — changer de secteur supprime les réponses devenues non pertinentes', function () {
    $resolver = new VisibilityResolver;
    $answers = diagnosticAnswers([
        'sector' => 'school', 'school_size' => 'lt_300', 'pains' => ['school_fees', 'school_grades', 'stock_gaps'],
    ]);

    expect($resolver->prune(diagnosticV1(), $answers))->toHaveKey('school_size');

    $pruned = $resolver->prune(diagnosticV1(), [...$answers, 'sector' => 'retail']);

    expect($pruned)->not->toHaveKey('school_size')
        ->and($pruned['pains'])->toBe(['stock_gaps']);
});

it('T8 — mêmes réponses, résultat JSON strictement identique', function () {
    $answers = diagnosticAnswers([
        'sector' => 'retail', 'sites' => '4_10', 'current_tools' => ['excel', 'pos_software'], 'existing_satisfaction' => 'partial',
        'pains' => ['cash_control', 'stock_gaps', 'customer_followup', 'management_reports'], 'mobile_money' => 'yes_now',
    ]);
    $shuffled = $answers;
    $shuffled['pains'] = array_reverse($answers['pains']);
    $shuffled = array_reverse($shuffled, true);

    $first = json_encode(runDiagnostic($answers)->toArray());

    expect(json_encode(runDiagnostic($answers)->toArray()))->toBe($first)
        ->and(json_encode(runDiagnostic($shuffled)->toArray()))->toBe($first);
});

it('T9 — aucun module à 3 : le meilleur à 2 est retenu avec scope_unclear', function () {
    $outcome = runDiagnostic(diagnosticAnswers([
        'current_tools' => ['excel'], 'pains' => '__unknown', 'main_goal' => 'growth',
    ]));

    expect(modules($outcome))->toBe(['MOD_CRM' => 2])
        ->and(array_column($outcome->clarifications, 'key'))->toContain('scope_unclear');
});

it('T10 — aucun module à 2 : liste vide, jamais de recommandation inventée', function () {
    $outcome = runDiagnostic(diagnosticAnswers([
        'current_tools' => ['excel'], 'pains' => '__unknown', 'main_goal' => 'time',
    ]));

    expect($outcome->recommendations)->toBe([])
        ->and($outcome->needsConversation())->toBeTrue()
        ->and(array_column($outcome->clarifications, 'key'))->toContain('scope_unclear')
        ->and(Catalog::NO_RECOMMENDATION_MESSAGE)->toBe('Votre besoin mérite un court échange pour être précisé.');
});

it('limite les recommandations à 5 modules triés par poids', function () {
    $outcome = runDiagnostic(diagnosticAnswers([
        'sector' => 'retail', 'team_size' => '51_200', 'sites' => '10_plus', 'current_tools' => ['excel'],
        'pains' => ['stock_gaps', 'slow_invoicing', 'customer_followup', 'hr_payroll'],
        'field_work' => 'many', 'connectivity' => 'unstable', 'mobile_money' => 'yes_now', 'main_goal' => 'growth',
    ]));

    $weights = array_values(modules($outcome));

    expect($outcome->recommendations)->toHaveCount(5)
        ->and($weights)->toBe(collect($weights)->sortDesc()->values()->all());
});

it('limite les priorités à 3, sans complément quand elles sont déjà 3', function () {
    $outcome = runDiagnostic(diagnosticAnswers([
        'pains' => ['stock_gaps', 'cash_control', 'slow_invoicing', 'hr_payroll'], 'main_goal' => 'growth',
    ]));

    expect($outcome->pains)->toHaveCount(3)
        ->and(array_column($outcome->pains, 'source'))->each->toBe('rule');
});

it('chaque recommandation justifie son « Pourquoi ? » par des questions', function () {
    $outcome = runDiagnostic(diagnosticAnswers(['sector' => 'retail', 'pains' => ['stock_gaps']]));
    $stock = collect($outcome->recommendations)->firstWhere('module', 'MOD_STOCK_POS');

    expect($stock['reasons'])->toBe([
        ['rule_key' => 'r_stock_pain', 'explanation' => 'Vous signalez des écarts ou ruptures de stock.', 'question_keys' => ['pains']],
        ['rule_key' => 'r_retail_base', 'explanation' => 'Dans le commerce, stock et caisse sont le socle de la gestion.', 'question_keys' => ['sector']],
    ]);
});

it('se reconstruit à l\'identique depuis sa forme sérialisée', function () {
    $outcome = runDiagnostic(diagnosticAnswers(['sector' => 'school', 'pains' => ['school_fees']]));
    $restored = DiagnosticOutcome::fromArray(json_decode(json_encode($outcome->toArray()), true));

    expect($restored->toArray())->toEqual($outcome->toArray())
        ->and($restored->maturityLabel())->toBe('Démarrage');
});

it('le template v1 est valide', function () {
    expect(diagnosticV1()->validate())->toBe([])
        ->and(diagnosticV1()->steps())->toBe([1, 2, 3, 4]);
});
