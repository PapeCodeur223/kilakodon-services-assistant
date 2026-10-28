<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/**
 * Questionnaire v1 tel qu'il est seedé (database/seeders/content/diagnostic-v1.php).
 */
function diagnosticV1(): App\Domain\Diagnostic\Engine\TemplateDefinition
{
    static $template;

    return $template ??= App\Domain\Diagnostic\Engine\TemplateDefinition::fromArray(require dirname(__DIR__).'/database/seeders/content/diagnostic-v1.php');
}

/**
 * Réponses neutres (aucune règle de recommandation ne se déclenche), surchargées par $overrides.
 * Une valeur null retire la réponse.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function diagnosticAnswers(array $overrides = []): array
{
    $answers = array_merge([
        'sector' => 'other',
        'team_size' => '1_5',
        'sites' => '1',
        'current_tools' => ['paper'],
        'pains' => '__unknown',
        'field_work' => 'none',
        'connectivity' => 'stable',
        'mobile_money' => 'no',
        'main_goal' => 'time',
        'timeline' => 'asap',
        'budget_band' => '500k_2m',
        'decision_role' => 'decider',
    ], $overrides);

    return array_filter($answers, fn (mixed $value): bool => $value !== null);
}
