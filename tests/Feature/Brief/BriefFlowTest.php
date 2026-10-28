<?php

use App\Domain\Brief\Actions\RequestBrief;
use App\Domain\Brief\Actions\RevokeBriefAccess;
use App\Domain\Brief\Actions\ValidateBrief;
use App\Domain\Brief\Data\BriefCorrections;
use App\Domain\Brief\Enums\BriefStatus;
use App\Domain\Brief\Enums\SummarySource;
use App\Domain\Brief\Exceptions\BriefRequestException;
use App\Domain\Brief\Models\Brief;
use App\Domain\Brief\Models\BriefVersion;
use App\Domain\Brief\Services\BriefAccess;
use App\Domain\Crm\Actions\CreateLeadFromBrief;
use App\Domain\Crm\Data\ProspectDetails;
use App\Domain\Crm\Enums\ConsentPurpose;
use App\Domain\Crm\Enums\ConsentSource;
use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Crm\Enums\LeadType;
use App\Domain\Crm\Models\Consent;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Lead;
use App\Domain\Diagnostic\Actions\SaveDiagnosticAnswers;
use App\Domain\Diagnostic\Enums\SessionStatus;
use App\Domain\Platform\Models\OutboundMessage;
use App\Domain\Platform\Models\User;
use App\Domain\Platform\Services\Settings;

function prospect(array $overrides = []): ProspectDetails
{
    return new ProspectDetails(...array_merge([
        'fullName' => 'Moussa Traoré',
        'phone' => '74 66 64 89',
        'email' => 'Moussa@Example.test',
        'company' => 'Boutique Djoliba',
        'marketingOptIn' => false,
        'ip' => '196.200.10.1',
    ], $overrides));
}

function requestBrief(array $answers = ['sector' => 'retail', 'sites' => '2_3', 'pains' => ['stock_gaps'], 'budget_band' => '2m_5m', 'timeline' => '1_3m'], array $prospect = []): array
{
    ['session' => $session] = completedDiagnostic($answers, ['utm' => ['utm_source' => 'facebook']]);

    return ['session' => $session, ...app(RequestBrief::class)->handle($session, prospect($prospect))];
}

it('crée contact, consentements séparés, brief v1 et lead en une fois (P1)', function () {
    app(Settings::class)->set('notify.lead_recipients', ['equipe@example.test']);
    ['session' => $session, 'brief' => $brief, 'token' => $token, 'lead' => $lead] = requestBrief();

    $contact = Contact::query()->with('company')->sole();
    expect($contact->phone_e164)->toBe('+22374666489')
        ->and($contact->email)->toBe('moussa@example.test')
        ->and($contact->company?->name)->toBe('Boutique Djoliba')
        ->and($contact->company?->sector_code)->toBe('retail')
        ->and($contact->company?->sites_band)->toBe('2_3');

    $consents = Consent::query()->where('contact_id', $contact->id)->get();
    expect($consents)->toHaveCount(2)
        ->and($consents->every(fn ($c) => $c->source === ConsentSource::BriefForm))->toBeTrue()
        ->and($consents->firstWhere('purpose', ConsentPurpose::Marketing)->granted)->toBeFalse();

    expect($brief->reference)->toMatch('/^KLK-\d{4}-0001$/')
        ->and($brief->status)->toBe(BriefStatus::Draft)
        ->and($brief->access_token_hash)->toBe(hash('sha256', $token))
        ->and($brief->access_expires_at->diffInDays(now()->addDays(30)))->toBeLessThan(1)
        ->and($session->refresh()->status)->toBe(SessionStatus::Converted);

    $version = $brief->currentVersion();
    expect($version->version)->toBe(1)
        ->and($version->summary_source)->toBe(SummarySource::Template)
        ->and($version->declared['contact']['company'])->toBe('Boutique Djoliba')
        ->and(collect($version->declared['answers'])->firstWhere('key', 'sector')['answer'])->toBe('Commerce de détail / boutique')
        ->and(array_column($version->recommended['recommendations'], 'module'))->toContain('MOD_STOCK_POS', 'MOD_MULTISITE');

    expect($lead->type)->toBe(LeadType::Diagnostic)
        ->and($lead->stage)->toBe(LeadStage::New)
        ->and($lead->brief_id)->toBe($brief->id)
        ->and($lead->source)->toBe('facebook')
        ->and($lead->budget_band)->toBe('2m_5m')
        ->and($lead->timeline)->toBe('1_3m')
        ->and($lead->maturity_level)->toBe(1)
        ->and($lead->message)->toContain($brief->reference);

    expect(OutboundMessage::query()->pluck('template', 'to')->all())->toBe([
        'equipe@example.test' => 'lead_new_internal',
        'moussa@example.test' => 'brief_ack',
    ]);
});

it('refuse une seconde demande pour le même diagnostic, sans toucher au contact ni au lien', function () {
    ['session' => $session, 'brief' => $brief, 'token' => $first] = requestBrief();

    expect(fn () => app(RequestBrief::class)->handle($session->refresh(), prospect(['fullName' => 'Usurpateur', 'phone' => '97 46 28 39'])))
        ->toThrow(BriefRequestException::class, $brief->reference);

    expect(Brief::query()->count())->toBe(1)
        ->and(Lead::query()->count())->toBe(1)
        ->and(Contact::query()->count())->toBe(1)
        ->and(Contact::query()->sole()->full_name)->toBe('Moussa Traoré')
        ->and(app(BriefAccess::class)->find($first)?->id)->toBe($brief->id);
});

it('ne permet pas d\'annuler une révocation en redemandant le brief depuis le lien de reprise (BRF-04)', function () {
    ['session' => $session, 'brief' => $brief, 'token' => $token] = requestBrief();
    app(RevokeBriefAccess::class)->handle($brief);

    expect(fn () => app(RequestBrief::class)->handle($session->refresh(), prospect()))->toThrow(BriefRequestException::class);

    $brief->refresh();
    expect($brief->isRevoked())->toBeTrue()
        ->and($brief->access_token_hash)->toBe(hash('sha256', $token));
});

it('refuse un brief sur une restitution périmée (réponses modifiées après le calcul)', function () {
    ['session' => $session] = completedDiagnostic(['sector' => 'retail', 'pains' => ['stock_gaps']]);
    app(SaveDiagnosticAnswers::class)->handle($session, [...$session->answerMap(), 'sector' => 'school'], 1);

    expect(fn () => app(RequestBrief::class)->handle($session->refresh(), prospect()))->toThrow(BriefRequestException::class)
        ->and(Brief::query()->count())->toBe(0)
        ->and(Contact::query()->count())->toBe(0);
});

it('garantit un lead unique par brief, même appelé deux fois', function () {
    ['brief' => $brief, 'lead' => $lead] = requestBrief();

    expect(app(CreateLeadFromBrief::class)->handle($brief)->id)->toBe($lead->id)
        ->and(Lead::query()->count())->toBe(1);
});

it('suggère un doublon au même numéro sans jamais fusionner', function () {
    requestBrief();
    $second = requestBrief(prospect: ['fullName' => 'M. Traoré', 'email' => null, 'phone' => '+223 74 66 64 89']);

    expect(Contact::query()->count())->toBe(2)
        ->and($second['duplicates'])->toHaveCount(1)
        ->and($second['lead']->contact_id)->not->toBe($second['duplicates']->first()->id);
});

it('refuse un numéro invalide sans rien créer', function () {
    ['session' => $session] = completedDiagnostic();

    expect(fn () => app(RequestBrief::class)->handle($session, prospect(['phone' => '12'])))->toThrow(InvalidArgumentException::class)
        ->and(Brief::query()->count())->toBe(0)
        ->and(Contact::query()->count())->toBe(0);
});

it('valide en créant une version figée avec les corrections, recommandations inchangées (BRF-02)', function () {
    ['brief' => $brief] = requestBrief();
    $v1 = $brief->currentVersion();

    $v2 = app(ValidateBrief::class)->handle($brief, new BriefCorrections(
        company: 'Djoliba SARL',
        contextNote: 'Deux boutiques à Bamako, une à Ségou.',
        summary: $v1->summary.' Nous voulons démarrer par la caisse.',
        comment: 'Merci !',
    ));

    $brief->refresh();
    expect($brief->status)->toBe(BriefStatus::Validated)
        ->and($brief->validated_at)->not->toBeNull()
        ->and($brief->current_version)->toBe(2)
        ->and($v2->summary_source)->toBe(SummarySource::ProspectEdited)
        ->and($v2->declared['contact']['company'])->toBe('Djoliba SARL')
        ->and($v2->declared['context_note'])->toBe('Deux boutiques à Bamako, une à Ségou.')
        ->and($v2->recommended)->toBe($v1->recommended)
        ->and($v2->prospect_comment)->toBe('Merci !')
        ->and(BriefVersion::query()->find($v1->id)->summary)->toBe($v1->summary);

    // Résumé inchangé : la source d'origine est conservée.
    $v3 = app(ValidateBrief::class)->handle($brief, new BriefCorrections('Djoliba SARL', null, $v2->summary));
    expect($v3->version)->toBe(3)->and($v3->summary_source)->toBe(SummarySource::ProspectEdited);
});

it('interdit toute modification d\'une version figée', function () {
    ['brief' => $brief] = requestBrief();

    expect(fn () => $brief->currentVersion()->update(['summary' => 'x']))->toThrow(LogicException::class)
        ->and(fn () => $brief->currentVersion()->delete())->toThrow(LogicException::class);
});

it('refuse la validation d\'un lien expiré ou révoqué (BRF-04)', function () {
    ['brief' => $brief] = requestBrief();
    app(RevokeBriefAccess::class)->handle($brief);

    expect($brief->refresh()->isRevoked())->toBeTrue()
        ->and(fn () => app(ValidateBrief::class)->handle($brief, new BriefCorrections(null, null, 'Résumé')))->toThrow(InvalidArgumentException::class);

    ['brief' => $other] = requestBrief();
    $other->forceFill(['access_expires_at' => now()->subMinute()])->save();
    expect($other->isAccessible())->toBeFalse();
});

it('ouvre les briefs au commercial et à l\'admin, jamais à l\'éditeur ni en écriture', function (string $role, bool $allowed) {
    ['brief' => $brief] = requestBrief();
    $user = User::factory()->{$role}()->create();

    expect($user->can('viewAny', Brief::class))->toBe($allowed)
        ->and($user->can('view', $brief))->toBe($allowed)
        ->and($user->can('revoke', $brief))->toBe($allowed)
        ->and($user->can('update', $brief))->toBeFalse()
        ->and($user->can('delete', $brief))->toBeFalse();
})->with([
    'admin' => ['admin', true],
    'sales' => ['sales', true],
    'editor' => ['editor', false],
]);
