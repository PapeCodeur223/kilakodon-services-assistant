<?php

use App\Domain\Diagnostic\Engine\ConditionEvaluator;
use App\Domain\Diagnostic\Engine\RuleDefinition;
use App\Domain\Diagnostic\Engine\VisibilityResolver;

/**
 * Un test positif et un test négatif par règle de DIAGNOSTIC_SPEC §4 (TRD §6).
 * Les réponses sont nettoyées comme en production avant l'évaluation.
 */
function ruleFires(string $ruleKey, array $overrides): bool
{
    $rule = collect(diagnosticV1()->rules)->first(fn (RuleDefinition $r): bool => $r->key === $ruleKey);
    expect($rule)->not->toBeNull("Règle {$ruleKey} absente du template");

    $answers = (new VisibilityResolver)->prune(diagnosticV1(), diagnosticAnswers($overrides));

    return (new ConditionEvaluator)->passes($rule->conditions, $answers);
}

dataset('rules', [
    'r_stock_pain' => ['r_stock_pain', ['pains' => ['stock_gaps']], ['pains' => ['cash_control']]],
    'r_cash_pain' => ['r_cash_pain', ['pains' => ['cash_control']], ['pains' => ['stock_gaps']]],
    'r_retail_base' => ['r_retail_base', ['sector' => 'wholesale'], ['sector' => 'other']],
    'r_invoicing' => ['r_invoicing', ['pains' => ['slow_invoicing']], ['pains' => ['stock_gaps']]],
    'r_crm' => ['r_crm', ['pains' => ['customer_followup']], ['pains' => ['slow_invoicing']]],
    'r_growth_crm' => ['r_growth_crm', ['main_goal' => 'growth'], ['main_goal' => 'time']],
    'r_reports' => ['r_reports', ['pains' => ['management_reports']], ['pains' => ['hr_payroll']]],
    'r_visibility_goal' => ['r_visibility_goal', ['main_goal' => 'visibility'], ['main_goal' => 'image']],
    'r_hr' => ['r_hr', ['pains' => ['hr_payroll']], ['pains' => ['management_reports']]],
    'r_hr_size' => ['r_hr_size', ['team_size' => '200_plus'], ['team_size' => '21_50']],
    'r_multisite' => ['r_multisite', ['sites' => '10_plus'], ['sites' => '1']],
    'r_multisite_pain' => ['r_multisite_pain', ['pains' => ['multisite_coordination']], ['pains' => ['field_teams']]],
    'r_field (terrain)' => ['r_field', ['field_work' => 'many'], ['field_work' => 'some']],
    'r_field (priorité)' => ['r_field', ['pains' => ['field_teams']], ['pains' => ['stock_gaps']]],
    'r_field_some' => ['r_field_some', ['field_work' => 'some'], ['field_work' => 'many']],
    'r_mm' => ['r_mm', ['mobile_money' => 'yes_now'], ['mobile_money' => 'no']],
    'r_offline' => ['r_offline', ['connectivity' => 'very_unstable'], ['connectivity' => 'stable']],
    'r_school' => ['r_school', ['sector' => 'school'], ['sector' => 'retail']],
    'r_school_fees' => ['r_school_fees', ['sector' => 'school', 'pains' => ['school_fees']], ['sector' => 'retail', 'pains' => ['school_fees']]],
    'r_school_grades' => ['r_school_grades', ['sector' => 'school', 'pains' => ['school_grades']], ['sector' => 'other', 'pains' => ['school_grades']]],
    'r_bakery' => ['r_bakery', ['sector' => 'bakery_food'], ['sector' => 'retail']],
    'r_bakery_waste' => ['r_bakery_waste', ['sector' => 'bakery_food', 'pains' => ['production_waste']], ['sector' => 'retail', 'pains' => ['production_waste']]],
    'r_specific_sector' => ['r_specific_sector', ['sector' => 'health'], ['sector' => 'retail']],
    'r_erp_paper_big' => ['r_erp_paper_big', ['team_size' => '21_50', 'current_tools' => ['excel']], ['team_size' => '21_50', 'current_tools' => ['excel', 'erp']]],
    'r_erp_paper_big (petite équipe)' => ['r_erp_paper_big', ['team_size' => '51_200', 'current_tools' => ['none']], ['team_size' => '6_20', 'current_tools' => ['paper']]],
    'r_legacy (insatisfait)' => ['r_legacy', ['current_tools' => ['pos_software'], 'existing_satisfaction' => 'no'], ['current_tools' => ['pos_software'], 'existing_satisfaction' => 'ok']],
    'r_legacy (priorité)' => ['r_legacy', ['pains' => ['legacy_software']], ['pains' => ['stock_gaps']]],
    'r_partial' => ['r_partial', ['current_tools' => ['erp'], 'existing_satisfaction' => 'partial'], ['current_tools' => ['paper'], 'existing_satisfaction' => 'partial']],
    'r_modernize' => ['r_modernize', ['main_goal' => 'modernize'], ['main_goal' => 'control']],
    'r_excel_migration' => ['r_excel_migration', ['current_tools' => ['excel']], ['current_tools' => ['paper']]],
    'r_image (objectif)' => ['r_image', ['main_goal' => 'image'], ['main_goal' => 'time']],
    'r_image (priorité)' => ['r_image', ['pains' => ['online_presence']], ['pains' => ['stock_gaps']]],
    'r_budget_small' => ['r_budget_small', ['budget_band' => 'lt_500k'], ['budget_band' => 'gt_15m']],
    'r_unknown_budget (je ne sais pas)' => ['r_unknown_budget', ['budget_band' => '__unknown'], ['budget_band' => '2m_5m']],
    'r_unknown_budget (sans réponse)' => ['r_unknown_budget', ['budget_band' => null], ['budget_band' => 'lt_500k']],
    'r_exploring' => ['r_exploring', ['timeline' => 'exploring'], ['timeline' => 'asap']],
    'r_not_decider' => ['r_not_decider', ['decision_role' => 'researcher'], ['decision_role' => 'decider']],
]);

it('se déclenche sur le cas positif et pas sur le cas négatif', function (string $rule, array $positive, array $negative) {
    expect(ruleFires($rule, $positive))->toBeTrue()
        ->and(ruleFires($rule, $negative))->toBeFalse();
})->with('rules');

it('couvre toutes les règles du template v1', function () {
    expect(collect(diagnosticV1()->rules)->pluck('key')->sort()->values()->all())->toHaveCount(32);
});

it('les réponses neutres ne déclenchent aucune recommandation', function () {
    $answers = (new VisibilityResolver)->prune(diagnosticV1(), diagnosticAnswers());
    $fired = collect(diagnosticV1()->rules)
        ->filter(fn (RuleDefinition $r): bool => (new ConditionEvaluator)->passes($r->conditions, $answers))
        ->filter(fn (RuleDefinition $r): bool => collect($r->effects)->contains('type', 'recommend'))
        ->pluck('key');

    expect($fired->all())->toBe([]);
});
