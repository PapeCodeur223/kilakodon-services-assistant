<?php

use App\Domain\Crm\Actions\ProcessPrivacyRequest;
use App\Domain\Crm\Enums\PrivacyRequestStatus;
use App\Domain\Crm\Enums\PrivacyRequestType;
use App\Domain\Crm\Exceptions\CrmException;
use App\Domain\Crm\Models\Consent;
use App\Domain\Crm\Models\PrivacyRequest;
use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\AuditLog;
use App\Domain\Platform\Models\OutboundMessage;
use App\Domain\Platform\Models\User;
use App\Filament\Resources\PrivacyRequests\Pages\EditPrivacyRequest;
use App\Filament\Resources\PrivacyRequests\PrivacyRequestResource;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Spatie\Honeypot\Honeypot;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    RateLimiter::clear('privacy');
});

/**
 * @return array<string, string>
 */
function privacyForm(array $overrides = []): array
{
    $honeypot = app(Honeypot::class);
    $fields = [$honeypot->nameFieldName() => '', $honeypot->validFromFieldName() => $honeypot->encryptedValidFrom()];
    test()->travel(5)->seconds();

    return array_merge(['name' => 'Awa Traoré', 'channel' => '74 66 64 89', 'type' => 'erasure', 'details' => ''], $fields, $overrides);
}

it('propose « Gérer mes données » dans le pied de page vers un formulaire dédié (PRV-02)', function () {
    $this->get('/')->assertOk()->assertSee(route('privacy.form'), false);

    $this->get('/mes-donnees')->assertOk()
        ->assertSee('Gérer')
        ->assertSee('Recevoir une copie de mes données')
        ->assertSee('Supprimer mes données')
        ->assertSee('name="channel"', false);
});

it('enregistre la demande « reçue », hache l’IP et prévient les administrateurs sans coordonnées', function () {
    User::factory()->admin()->create(['email' => 'direction@example.test']);

    $this->post('/mes-donnees', privacyForm())->assertRedirect(route('privacy.form'));

    $request = PrivacyRequest::query()->sole();
    expect($request->status)->toBe(PrivacyRequestStatus::Received)
        ->and($request->type)->toBe(PrivacyRequestType::Erasure)
        ->and($request->contact_id)->toBeNull()
        ->and($request->ip_hash)->toHaveLength(64);

    $mail = OutboundMessage::query()->where('template', 'privacy_request_internal')->sole();
    expect($mail->to)->toBe('direction@example.test')
        ->and(json_encode($mail->payload))->not->toContain('Awa')->not->toContain('6664');

    $this->get('/mes-donnees')->assertSee('Votre demande est bien enregistrée');
});

it('exige des précisions pour une correction et refuse les robots', function () {
    $this->post('/mes-donnees', privacyForm(['type' => 'rectification']))->assertSessionHasErrors('details');
    $this->post('/mes-donnees', privacyForm(['channel' => 'appelez-moi']))->assertSessionHasErrors('channel');

    $honeypot = app(Honeypot::class);
    $this->post('/mes-donnees', ['name' => 'Bot', 'channel' => 'bot@example.test', 'type' => 'access', $honeypot->nameFieldName() => 'rempli', $honeypot->validFromFieldName() => $honeypot->encryptedValidFrom()]);

    expect(PrivacyRequest::query()->count())->toBe(0);
});

it('réserve les demandes de droits aux administrateurs', function () {
    $request = PrivacyRequest::query()->create(['requester_name' => 'Awa', 'requester_channel' => 'awa@example.test', 'type' => PrivacyRequestType::Access]);

    $this->actingAs(User::factory()->admin()->withMfa()->create())->get(PrivacyRequestResource::getUrl())->assertOk()->assertSee('Awa');
    $this->get(PrivacyRequestResource::getUrl('edit', ['record' => $request]))->assertOk();

    foreach (['sales', 'editor'] as $role) {
        $this->actingAs(User::factory()->{$role}()->create())->get(PrivacyRequestResource::getUrl())->assertForbidden();
    }
});

it('traite une demande de bout en bout : vérification, export, anonymisation, clôture', function () {
    ['contact' => $contact] = privacyProspect();
    $admin = User::factory()->admin()->withMfa()->create();
    $request = PrivacyRequest::query()->create(['requester_name' => 'Awa Traoré', 'requester_channel' => '74 66 64 89', 'type' => PrivacyRequestType::Erasure, 'contact_id' => $contact->id]);

    $this->actingAs($admin);

    Livewire::test(EditPrivacyRequest::class, ['record' => $request->getRouteKey()])
        ->assertActionHidden('export')
        ->assertActionHidden('anonymize')
        ->callAction('verify')
        ->assertActionVisible('export')
        ->callAction('export')
        ->callAction('anonymize', ['confirmation' => 'ANONYMISER'])
        ->callAction('done', ['note' => 'Données supprimées, demandeur prévenu par WhatsApp.']);

    $request->refresh();
    expect($request->status)->toBe(PrivacyRequestStatus::Done)
        ->and($request->verified_at)->not->toBeNull()
        ->and($request->handled_by)->toBe($admin->id)
        ->and($contact->refresh()->anonymized_at)->not->toBeNull();

    expect(AuditLog::query()->whereIn('action', [AuditAction::Exported, AuditAction::Anonymized])->count())->toBe(2);
});

it('retire le consentement marketing sur opposition, par une nouvelle ligne tracée', function () {
    ['contact' => $contact] = privacyProspect();
    $contact->forceFill(['marketing_opt_in' => true])->save();
    $this->actingAs(User::factory()->admin()->withMfa()->create());
    $request = PrivacyRequest::query()->create(['requester_name' => 'Awa', 'requester_channel' => 'x', 'type' => PrivacyRequestType::Objection, 'contact_id' => $contact->id, 'verified_at' => now(), 'status' => PrivacyRequestStatus::InProgress]);

    Livewire::test(EditPrivacyRequest::class, ['record' => $request->getRouteKey()])->callAction('withdrawMarketing');

    expect($contact->refresh()->marketing_opt_in)->toBeFalse()
        ->and(Consent::query()->where('contact_id', $contact->id)->where('source', 'privacy_request')->where('granted', false)->exists())->toBeTrue();
});

it('annule la vérification si le contact relié change ensuite (revue M3)', function () {
    ['contact' => $contact] = privacyProspect();
    $other = makeLead()->contact;
    $this->actingAs(User::factory()->admin()->withMfa()->create());
    $request = PrivacyRequest::query()->create(['requester_name' => 'Awa', 'requester_channel' => 'x', 'type' => PrivacyRequestType::Erasure, 'contact_id' => $contact->id]);

    Livewire::test(EditPrivacyRequest::class, ['record' => $request->getRouteKey()])
        ->callAction('verify')
        ->assertActionVisible('anonymize')
        ->fillForm(['contact_id' => $other->id])
        ->call('save')
        ->assertActionHidden('anonymize')
        ->assertActionHidden('export');

    $request->refresh();
    expect($request->contact_id)->toBe($other->id)
        ->and($request->verified_at)->toBeNull()
        ->and($request->status)->toBe(PrivacyRequestStatus::Verifying);
});

it('refuse l’anonymisation sans la confirmation tapée', function () {
    ['contact' => $contact] = privacyProspect();
    $this->actingAs(User::factory()->admin()->withMfa()->create());
    $request = PrivacyRequest::query()->create(['requester_name' => 'Awa', 'requester_channel' => 'x', 'type' => PrivacyRequestType::Erasure, 'contact_id' => $contact->id, 'verified_at' => now(), 'status' => PrivacyRequestStatus::InProgress]);

    Livewire::test(EditPrivacyRequest::class, ['record' => $request->getRouteKey()])
        ->callAction('anonymize', ['confirmation' => 'oui'])
        ->assertHasActionErrors(['confirmation']);

    expect($contact->refresh()->anonymized_at)->toBeNull();
});

it('exige un motif pour rejeter et ne rouvre jamais une demande close', function () {
    $admin = User::factory()->admin()->create();
    $request = PrivacyRequest::query()->create(['requester_name' => 'X', 'requester_channel' => 'x@example.test', 'type' => PrivacyRequestType::Access]);
    $process = app(ProcessPrivacyRequest::class);

    expect(fn () => $process->close($request, $admin, PrivacyRequestStatus::Rejected, ' '))->toThrow(CrmException::class)
        ->and(fn () => $process->close($request, $admin, PrivacyRequestStatus::Done, 'ok'))->toThrow(CrmException::class);

    $process->close($request, $admin, PrivacyRequestStatus::Rejected, 'Identité impossible à vérifier.');
    expect(fn () => $process->verify($request->refresh(), $admin))->toThrow(CrmException::class);
});
