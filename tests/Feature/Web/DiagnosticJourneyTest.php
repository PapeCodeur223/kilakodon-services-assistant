<?php

use App\Domain\Brief\Actions\RequestBrief;
use App\Domain\Brief\Models\Brief;
use App\Domain\Brief\Services\BriefAccess;
use App\Domain\Crm\Data\ProspectDetails;
use App\Domain\Crm\Models\Lead;
use App\Domain\Diagnostic\Enums\SessionStatus;
use App\Domain\Diagnostic\Models\DiagnosticSession;
use App\Domain\Platform\Support\PublicToken;
use App\Livewire\Brief\Show;
use App\Livewire\Diagnostic\Wizard;
use Livewire\Livewire;

beforeEach(fn () => seedDiagnosticTemplate());

/**
 * Répond à l'étape courante du composant puis clique « Continuer ».
 *
 * @param  array<string, mixed>  $answers
 */
function answerStep($component, array $answers)
{
    foreach ($answers as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $v) {
                $component->call('toggle', $key, $v);
            }
        } else {
            $component->set('answers.'.$key, $value);
        }
    }

    return $component->call('next');
}

it('affiche le diagnostic, indexable, avec le secteur pré-rempli et modifiable (DIA-04)', function () {
    $this->get('/diagnostic?secteur=school&entry=sector:school')
        ->assertOk()
        ->assertSee('Votre activité')
        ->assertSee('Combien d&#039;élèves accueillez-vous ?', false)
        ->assertDontSee('noindex');

    Livewire::test(Wizard::class, ['sector' => 'school'])
        ->assertSet('answers.sector', 'school')
        ->set('answers.sector', 'retail')
        ->assertSet('answers.sector', 'retail')
        ->assertDontSee('Combien d\'élèves');
});

it('ne crée aucune session pour une simple visite', function () {
    $this->get('/diagnostic')->assertOk();

    expect(DiagnosticSession::query()->count())->toBe(0);
});

it('bloque « Continuer » tant qu\'une question requise est sans réponse', function () {
    Livewire::test(Wizard::class)
        ->set('answers.sector', 'retail')
        ->call('next')
        ->assertSet('step', 1)
        ->assertSet('missing', ['team_size', 'sites'])
        ->assertSee('Choisissez une réponse pour continuer.');

    expect(DiagnosticSession::query()->count())->toBe(0);
});

it('applique « Je ne sais pas », le choix exclusif et le maximum de 4 priorités', function () {
    $component = Livewire::test(Wizard::class);
    answerStep($component, ['sector' => 'retail', 'team_size' => '6_20', 'sites' => '1']);

    $component->call('toggle', 'current_tools', 'excel')
        ->call('toggle', 'current_tools', 'none')
        ->assertSet('answers.current_tools', ['none'])
        ->call('unknown', 'pains')
        ->assertSet('answers.pains', '__unknown')
        ->call('toggle', 'pains', 'stock_gaps')
        ->call('toggle', 'pains', 'cash_control')
        ->call('toggle', 'pains', 'slow_invoicing')
        ->call('toggle', 'pains', 'customer_followup')
        ->call('toggle', 'pains', 'hr_payroll')
        ->assertHasErrors('answers.pains')
        ->assertSet('answers.pains', ['stock_gaps', 'cash_control', 'slow_invoicing', 'customer_followup']);
});

it('parcours P1 complet : diagnostic → restitution → coordonnées → brief → validation → PDF', function () {
    $component = Livewire::test(Wizard::class, ['entry' => 'home']);

    answerStep($component, ['sector' => 'retail', 'team_size' => '6_20', 'sites' => '2_3'])->assertSet('step', 2);
    $session = DiagnosticSession::query()->sole();
    expect($session->entry)->toBe('home');

    answerStep($component, ['current_tools' => ['excel', 'whatsapp'], 'pains' => ['stock_gaps', 'cash_control'], 'field_work' => 'none'])->assertSet('step', 3);
    answerStep($component, ['connectivity' => 'unstable', 'mobile_money' => 'wanted', 'main_goal' => 'time'])->assertSet('step', 4);
    answerStep($component, ['timeline' => '1_3m', 'decision_role' => 'decider', 'context_note' => 'Deux boutiques à Bamako.'])
        ->assertSet('screen', 'result')
        ->assertSee('Votre')
        ->assertSee('Outillé')
        ->assertSee('Gestion de stock &amp; caisse', false)
        ->assertSee('Pourquoi ?')
        ->assertSee('Vous signalez des écarts ou ruptures de stock.')
        ->assertSee('Quelles opérations doivent absolument fonctionner sans connexion ?');

    // Le visiteur voit sa restitution sans avoir laissé de coordonnées (PRD §7.4).
    expect(Lead::query()->count())->toBe(0);

    // Honeypot : un envoi plus rapide que le délai minimal est rejeté comme un robot.
    $this->travel(5)->seconds();

    $component->call('openBriefForm')
        ->assertSee('Recevoir mon brief')
        ->set('fullName', 'Awa Diarra')
        ->set('phone', '74 66 64 89')
        ->set('company', 'Boutiques Awa')
        ->call('requestBrief')
        ->assertHasErrors(['consentProcessing' => 'accepted']);

    $component->set('consentProcessing', true)->call('requestBrief')->assertHasNoErrors();

    $brief = Brief::query()->sole();
    $lead = Lead::query()->sole();
    expect($lead->brief_id)->toBe($brief->id)
        ->and($brief->reference)->toMatch('/^KLK-\d{4}-0001$/');

    // Le token n'est jamais stocké en clair : on en émet un nouveau pour suivre la redirection.
    $token = PublicToken::generate();
    $brief->forceFill(['access_token_hash' => $token['hash']])->save();

    $this->get(route('brief.show', ['token' => $token['token']]))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee($brief->reference)
        ->assertSee('Ce que vous nous avez dit')
        ->assertSee('Ce que nous recommandons')
        ->assertSee('Document de travail non contractuel');

    Livewire::test(Show::class, ['brief' => $brief, 'token' => $token['token']])
        ->assertSet('editing', true)
        ->assertSet('company', 'Boutiques Awa')
        ->assertSet('contextNote', 'Deux boutiques à Bamako.')
        ->set('summary', 'Nous voulons démarrer par la caisse.')
        ->call('validateBrief')
        ->assertSet('editing', false)
        ->assertSee('Votre brief est validé.')
        ->assertSee('Continuer sur WhatsApp')
        ->assertSee(rawurlencode($brief->reference));

    expect($brief->refresh()->current_version)->toBe(2);

    $pdf = $this->get(route('brief.pdf', ['token' => $token['token']]));
    $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect(substr((string) $pdf->getContent(), 0, 4))->toBe('%PDF');
});

it('reprend une session par son lien et renvoie 404 pour un token inconnu ou expiré (DIA-03)', function () {
    ['session' => $session, 'token' => $token] = completedDiagnostic(['sector' => 'retail', 'pains' => ['stock_gaps']]);

    $this->get(route('diagnostic.resume', ['token' => $token]))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('Gestion de stock &amp; caisse', false);

    $this->get(route('diagnostic.resume', ['token' => str_repeat('x', 43)]))->assertNotFound();
    $this->get('/diagnostic/reprendre/court')->assertNotFound();

    $session->forceFill(['expires_at' => now()->subDay()])->save();
    $this->get(route('diagnostic.resume', ['token' => $token]))->assertNotFound();
});

it('affiche « lien expiré » pour un brief expiré ou révoqué, 404 pour un token inconnu (BRF-04)', function () {
    ['session' => $session] = completedDiagnostic();
    ['brief' => $brief, 'token' => $token] = app(RequestBrief::class)
        ->handle($session, new ProspectDetails('Awa Diarra', '74 66 64 89'));

    $brief->forceFill(['access_expires_at' => now()->subMinute()])->save();

    $this->get(route('brief.show', ['token' => $token]))
        ->assertStatus(410)
        ->assertSee('Ce lien a')
        ->assertSee($brief->reference);
    $this->get(route('brief.pdf', ['token' => $token]))->assertStatus(410);

    $brief->forceFill(['access_expires_at' => now()->addDay(), 'access_revoked_at' => now()])->save();
    $this->get(route('brief.show', ['token' => $token]))->assertStatus(410);

    $this->get(route('brief.show', ['token' => PublicToken::generate()['token']]))->assertNotFound();
    expect(app(BriefAccess::class)->find('<script>'))->toBeNull();
});

it('n\'expose jamais les données d\'un brief dans le HTML d\'un autre token', function () {
    ['session' => $session] = completedDiagnostic();
    app(RequestBrief::class)->handle($session, new ProspectDetails('Awa Diarra', '74 66 64 89'));

    $this->get(route('brief.show', ['token' => PublicToken::generate()['token']]))
        ->assertNotFound()
        ->assertDontSee('Awa Diarra');
});

it('rouvre une session déjà convertie en lecture seule, sans erreur ni nouveau brief (M3)', function () {
    ['session' => $session, 'token' => $token] = completedDiagnostic(['sector' => 'retail', 'pains' => ['stock_gaps']]);
    ['brief' => $brief] = app(RequestBrief::class)
        ->handle($session, new ProspectDetails('Awa Diarra', '74 66 64 89'));

    $this->get(route('diagnostic.resume', ['token' => $token]))
        ->assertOk()
        ->assertSee('a déjà été demandé')
        ->assertSee($brief->reference)
        ->assertDontSee('Modifier mes réponses');

    Livewire::test(Wizard::class, ['session' => $session->refresh(), 'token' => $token])
        ->assertSet('briefReference', $brief->reference)
        ->call('editAnswers')
        ->assertSet('screen', 'result')
        ->call('openBriefForm')
        ->assertSet('showBriefForm', false)
        ->call('next')
        ->assertOk();

    expect(Brief::query()->count())->toBe(1);
});

it('marque la session « enregistrée » quand le visiteur la garde pour plus tard', function () {
    Livewire::test(Wizard::class)
        ->set('answers.sector', 'retail')
        ->call('saveForLater')
        ->assertSet('showResumeLink', true)
        ->assertSee('Vos réponses sont enregistrées.');

    expect(DiagnosticSession::query()->sole()->status)->toBe(SessionStatus::Saved);
});

it('rejette silencieusement une demande de brief envoyée trop vite (robot)', function () {
    ['session' => $session, 'token' => $token] = completedDiagnostic();

    Livewire::test(Wizard::class, ['session' => $session, 'token' => $token])
        ->call('openBriefForm')
        ->set('fullName', 'Robot')
        ->set('phone', '74 66 64 89')
        ->set('consentProcessing', true)
        ->call('requestBrief');

    expect(Brief::query()->count())->toBe(0)
        ->and(Lead::query()->count())->toBe(0);
});
