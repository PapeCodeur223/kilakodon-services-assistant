<?php

use App\Domain\Diagnostic\Actions\ActivateDiagnosticTemplate;
use App\Domain\Diagnostic\Actions\CompleteDiagnostic;
use App\Domain\Diagnostic\Actions\DuplicateDiagnosticTemplate;
use App\Domain\Diagnostic\Actions\PurgeDiagnosticSessions;
use App\Domain\Diagnostic\Actions\SaveDiagnosticAnswers;
use App\Domain\Diagnostic\Actions\StartDiagnosticSession;
use App\Domain\Diagnostic\Data\StartDiagnostic;
use App\Domain\Diagnostic\Enums\SessionStatus;
use App\Domain\Diagnostic\Jobs\GenerateDiagnosticSummary;
use App\Domain\Diagnostic\Models\DiagnosticAnswer;
use App\Domain\Diagnostic\Models\DiagnosticResult;
use App\Domain\Diagnostic\Models\DiagnosticSession;
use App\Domain\Diagnostic\Services\DiagnosticSessions;
use App\Domain\Diagnostic\Services\DiagnosticSummaryWriter;
use App\Domain\Platform\Services\Settings;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => seedDiagnosticTemplate());

it('démarre une session anonyme avec un token haché et une expiration à 30 jours', function () {
    ['session' => $session, 'token' => $token] = app(StartDiagnosticSession::class)->handle(new StartDiagnostic(ip: '196.200.10.1', entry: 'sector:retail'));

    expect($token)->toMatch('/^[A-Za-z0-9_-]{43}$/')
        ->and($session->resume_token_hash)->toBe(hash('sha256', $token))
        ->and($session->resume_token_hash)->not->toContain($token)
        ->and($session->ip_hash)->not->toContain('196.200')
        ->and($session->entry)->toBe('sector:retail')
        ->and($session->expires_at->diffInDays(now()->addDays(30)))->toBeLessThan(1)
        ->and(app(DiagnosticSessions::class)->findByToken($token)?->id)->toBe($session->id)
        ->and(app(DiagnosticSessions::class)->findByToken(str_repeat('a', 43)))->toBeNull()
        ->and(app(DiagnosticSessions::class)->findByToken('court'))->toBeNull();
});

it('pré-remplit un secteur valide comme réponse modifiable, ignore un secteur inconnu (DIA-04)', function () {
    ['session' => $valid] = app(StartDiagnosticSession::class)->handle(new StartDiagnostic(ip: '1.1.1.1', sector: 'school'));
    ['session' => $invalid] = app(StartDiagnosticSession::class)->handle(new StartDiagnostic(ip: '1.1.1.1', sector: '<script>'));

    expect($valid->answerMap())->toBe(['sector' => 'school'])
        ->and($valid->prefill)->toBe(['sector' => 'school'])
        ->and($invalid->answerMap())->toBe([])
        ->and($invalid->prefill)->toBeNull();

    app(SaveDiagnosticAnswers::class)->handle($valid, ['sector' => 'retail'], 1);
    expect($valid->refresh()->answerMap())->toBe(['sector' => 'retail']);
});

it('enregistre chaque étape et supprime les réponses devenues non pertinentes (DIA-01)', function () {
    ['session' => $session] = app(StartDiagnosticSession::class)->handle(new StartDiagnostic(ip: '1.1.1.1'));
    $save = app(SaveDiagnosticAnswers::class);

    $save->handle($session, ['sector' => 'school', 'school_size' => 'lt_300', 'team_size' => '6_20', 'sites' => '1'], 2);
    $save->handle($session, [...$session->refresh()->answerMap(), 'current_tools' => ['excel'], 'pains' => ['school_fees', 'stock_gaps']], 3);
    $save->handle($session, [...$session->refresh()->answerMap(), 'sector' => 'retail'], 3);

    $answers = $session->refresh()->answerMap();
    expect($answers)->not->toHaveKey('school_size')
        ->and($answers['pains'])->toBe(['stock_gaps'])
        ->and($session->current_step)->toBe(3)
        ->and(DiagnosticAnswer::query()->where('session_id', $session->id)->where('question_key', 'school_size')->exists())->toBeFalse();
});

it('calcule et enregistre la restitution, puis demande le résumé hors requête', function () {
    Queue::fake();
    ['session' => $session] = completedDiagnostic(['sector' => 'retail', 'pains' => ['stock_gaps']]);
    $result = $session->result()->firstOrFail();

    expect($session->status)->toBe(SessionStatus::Completed)
        ->and($result->template_version)->toBe(1)
        ->and(array_column($result->recommendations, 'module'))->toBe(['MOD_STOCK_POS'])
        ->and($result->recommendations[0]['weight'])->toBe(5)
        ->and($result->maturity_level)->toBe(1);

    Queue::assertPushed(GenerateDiagnosticSummary::class, fn ($job) => $job->resultId === $result->id);
});

it('refuse de calculer tant qu\'une question requise est sans réponse', function () {
    ['session' => $session] = app(StartDiagnosticSession::class)->handle(new StartDiagnostic(ip: '1.1.1.1'));
    app(SaveDiagnosticAnswers::class)->handle($session, diagnosticAnswers(['timeline' => null]), 4);

    app(CompleteDiagnostic::class)->handle($session);
})->throws(ValidationException::class);

it('garde la version du questionnaire de la session après une nouvelle activation (DIA-03)', function () {
    ['session' => $session] = app(StartDiagnosticSession::class)->handle(new StartDiagnostic(ip: '1.1.1.1'));

    $v2 = app(DuplicateDiagnosticTemplate::class)->handle($session->load('template')->template);
    app(ActivateDiagnosticTemplate::class)->handle($v2);

    app(SaveDiagnosticAnswers::class)->handle($session, diagnosticAnswers(), 4);
    $result = app(CompleteDiagnostic::class)->handle($session->refresh());

    expect($session->template->version)->toBe(1)
        ->and($result->template_version)->toBe(1);
});

it('rédige le résumé avec l\'IA (pilote fake) uniquement si elle est activée avec un budget', function () {
    ['session' => $session] = completedDiagnostic(['sector' => 'retail', 'pains' => ['stock_gaps']]);
    $result = $session->result()->firstOrFail();

    // Paramètres de test : IA coupée → aucun résumé IA, le gabarit sera affiché.
    expect($result->ai_summary)->toBeNull();

    app(Settings::class)->set('ai.enabled', true);
    app(Settings::class)->set('ai.daily_budget_usd', 1);
    (new GenerateDiagnosticSummary($result->id))->handle(app(DiagnosticSummaryWriter::class));

    $result->refresh();
    expect($result->ai_summary)->toContain('Gestion de stock & caisse')
        ->and($result->ai_summary_model)->toBe('fake');
});

it('n\'envoie au modèle que le DTO, sans coordonnées ni texte libre', function () {
    ['session' => $session] = completedDiagnostic(['context_note' => 'Mon numéro est le 74 66 64 89']);
    $payload = app(DiagnosticSummaryWriter::class)->payload($session->result()->firstOrFail()->toOutcome());

    expect($payload)->not->toContain('74 66')
        ->and($payload)->not->toContain('context_note')
        ->and(json_decode($payload, true))->toHaveKeys(['maturity_label', 'pains', 'recommendations', 'clarifications']);
});

it('rejette une reformulation qui cite un montant, une garantie ou une longueur hors bornes', function (string $text) {
    expect(app(DiagnosticSummaryWriter::class)->isAcceptable($text))->toBeFalse();
})->with([
    'montant' => [str_repeat('Votre projet avance bien. ', 15).'Comptez 500 000 FCFA.'],
    'garantie' => [str_repeat('Votre projet avance bien. ', 15).'Résultat garanti.'],
    'trop court' => ['Un résumé de quelques mots.'],
    'balise' => [str_repeat('Votre projet avance bien. ', 15).'<b>gras</b>'],
]);

it('produit un résumé gabarit sans échappement HTML parasite', function () {
    ['session' => $session] = completedDiagnostic(['sector' => 'retail', 'pains' => ['stock_gaps']]);
    $text = app(DiagnosticSummaryWriter::class)->template($session->result()->firstOrFail()->toOutcome());

    expect($text)->toContain('Nous recommandons d\'étudier en premier lieu : Gestion de stock & caisse.')
        ->and($text)->not->toContain('&#039;')
        ->and($text)->not->toContain('&amp;');
});

it('purge les sessions anonymes expirées et conserve les autres', function () {
    ['session' => $old] = completedDiagnostic();
    ['session' => $recent] = completedDiagnostic();
    $old->forceFill(['expires_at' => now()->subDay()])->save();

    $this->artisan('kh:diagnostic-purge')->assertSuccessful();

    expect(DiagnosticSession::query()->find($old->id))->toBeNull()
        ->and(DiagnosticResult::query()->where('session_id', $old->id)->exists())->toBeFalse()
        ->and(DiagnosticSession::query()->find($recent->id))->not->toBeNull()
        ->and(app(PurgeDiagnosticSessions::class)->handle())->toBe(0);
});
