<?php

use App\Domain\Diagnostic\Actions\CompleteDiagnostic;
use App\Domain\Diagnostic\Actions\SaveDiagnosticAnswers;
use App\Domain\Diagnostic\Actions\StartDiagnosticSession;
use App\Domain\Diagnostic\Data\StartDiagnostic;
use App\Domain\Diagnostic\Engine\TemplateDefinition;
use App\Domain\Diagnostic\Models\DiagnosticSession;
use App\Domain\Diagnostic\Models\DiagnosticTemplate;
use Database\Seeders\DiagnosticTemplateV1Seeder;
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
function diagnosticV1(): TemplateDefinition
{
    static $template;

    return $template ??= TemplateDefinition::fromArray(require dirname(__DIR__).'/database/seeders/content/diagnostic-v1.php');
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

/**
 * Seede et active le questionnaire v1 (tests fonctionnels).
 */
function seedDiagnosticTemplate(): DiagnosticTemplate
{
    test()->seed(DiagnosticTemplateV1Seeder::class);

    return DiagnosticTemplate::query()->active()->firstOrFail();
}

/**
 * Session de diagnostic terminée avec ses réponses (neutres par défaut, surchargées par $overrides).
 *
 * @param  array<string, mixed>  $overrides
 * @return array{session: DiagnosticSession, token: string}
 */
function completedDiagnostic(array $overrides = [], array $start = []): array
{
    if (! DiagnosticTemplate::query()->active()->exists()) {
        seedDiagnosticTemplate();
    }

    $started = app(StartDiagnosticSession::class)
        ->handle(new StartDiagnostic(...array_merge(['ip' => '196.200.10.1', 'entry' => 'home'], $start)));

    app(SaveDiagnosticAnswers::class)->handle($started['session'], diagnosticAnswers($overrides), 4);
    app(CompleteDiagnostic::class)->handle($started['session']);

    return ['session' => $started['session']->refresh(), 'token' => $started['token']];
}
