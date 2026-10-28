<?php

use App\Domain\Diagnostic\Actions\ActivateDiagnosticTemplate;
use App\Domain\Diagnostic\Actions\CreateDiagnosticTemplate;
use App\Domain\Diagnostic\Actions\DuplicateDiagnosticTemplate;
use App\Domain\Diagnostic\Engine\DiagnosticEngine;
use App\Domain\Diagnostic\Enums\TemplateStatus;
use App\Domain\Diagnostic\Exceptions\TemplateLockedException;
use App\Domain\Diagnostic\Models\DiagnosticTemplate;
use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\AuditLog;
use App\Domain\Platform\Models\User;
use Database\Seeders\DiagnosticTemplateV1Seeder;
use Illuminate\Validation\ValidationException;

it('seede le questionnaire v1 complet et actif, sans doublon', function () {
    $template = seedDiagnosticTemplate();
    $this->seed(DiagnosticTemplateV1Seeder::class);

    expect(DiagnosticTemplate::query()->count())->toBe(1)
        ->and($template->status)->toBe(TemplateStatus::Active)
        ->and($template->activated_at)->not->toBeNull()
        ->and($template->questions()->count())->toBe(15)
        ->and($template->rules()->count())->toBe(32)
        ->and($template->toDefinition()->validate())->toBe([]);
});

it('produit le même résultat depuis la base que depuis le fichier source', function () {
    $fromDatabase = seedDiagnosticTemplate()->toDefinition();
    $answers = diagnosticAnswers(['sector' => 'retail', 'sites' => '2_3', 'pains' => ['stock_gaps', 'cash_control'], 'connectivity' => 'unstable']);
    $engine = new DiagnosticEngine;

    expect($engine->run($fromDatabase, $answers)->toArray())->toBe($engine->run(diagnosticV1(), $answers)->toArray());
});

it('rend un template actif immuable, questions et règles comprises', function () {
    $template = seedDiagnosticTemplate();

    expect(fn () => $template->update(['name' => 'Autre']))->toThrow(TemplateLockedException::class)
        ->and(fn () => $template->questions()->first()->update(['label' => 'Modifié']))->toThrow(TemplateLockedException::class)
        ->and(fn () => $template->rules()->first()->delete())->toThrow(TemplateLockedException::class)
        ->and(fn () => $template->questions()->create(['key' => 'x', 'step' => 1, 'label' => 'X', 'type' => 'text']))->toThrow(TemplateLockedException::class)
        ->and(fn () => $template->delete())->toThrow(TemplateLockedException::class);
});

it('duplique en brouillon modifiable puis active la nouvelle version en retirant l\'ancienne', function () {
    $v1 = seedDiagnosticTemplate();
    $v2 = app(DuplicateDiagnosticTemplate::class)->handle($v1);

    expect($v2->version)->toBe(2)
        ->and($v2->status)->toBe(TemplateStatus::Draft)
        ->and($v2->questions()->count())->toBe(15)
        ->and($v2->rules()->count())->toBe(32);

    $v2->questions()->where('key', 'sector')->firstOrFail()->update(['label' => 'Votre secteur ?']);
    app(ActivateDiagnosticTemplate::class)->handle($v2);

    expect($v1->refresh()->status)->toBe(TemplateStatus::Retired)
        ->and($v2->refresh()->status)->toBe(TemplateStatus::Active)
        ->and(DiagnosticTemplate::query()->active()->count())->toBe(1)
        ->and(AuditLog::query()->where('action', AuditAction::TemplateActivated)->count())->toBe(2);
});

it('refuse d\'activer un template incohérent', function () {
    $template = app(CreateDiagnosticTemplate::class)->handle([
        'name' => 'Cassé', 'version' => 9,
        'questions' => [['key' => 'sector', 'step' => 1, 'type' => 'single', 'label' => 'Secteur', 'options' => [['value' => 'retail', 'label' => 'Commerce']]]],
        'rules' => [['key' => 'r_bad', 'priority' => 1, 'conditions' => ['all' => [['q' => 'inconnue', 'op' => 'eq', 'v' => 'x']]], 'effects' => [['type' => 'recommend', 'module' => 'MOD_NOPE', 'weight' => 3]]]],
    ]);

    expect(fn () => app(ActivateDiagnosticTemplate::class)->handle($template))->toThrow(ValidationException::class)
        ->and($template->refresh()->status)->toBe(TemplateStatus::Draft);
});

it('réserve les questionnaires à l\'administrateur', function (string $role, bool $allowed) {
    $template = seedDiagnosticTemplate();
    $user = User::factory()->{$role}()->create();

    expect($user->can('viewAny', DiagnosticTemplate::class))->toBe($allowed)
        ->and($user->can('duplicate', $template))->toBe($allowed)
        ->and($user->can('update', $template))->toBeFalse()
        ->and($user->can('delete', $template))->toBeFalse();
})->with([
    'admin' => ['admin', true],
    'sales' => ['sales', false],
    'editor' => ['editor', false],
]);
