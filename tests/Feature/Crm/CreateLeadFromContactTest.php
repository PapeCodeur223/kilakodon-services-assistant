<?php

use App\Domain\Crm\Actions\CreateLeadFromContact;
use App\Domain\Crm\Data\ContactRequest;
use App\Domain\Crm\Enums\ConsentPurpose;
use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Crm\Enums\LeadType;
use App\Domain\Crm\Models\Consent;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Lead;
use App\Domain\Platform\Enums\OutboundStatus;
use App\Domain\Platform\Models\OutboundMessage;
use App\Domain\Platform\Services\Settings;

function contactRequest(array $overrides = []): ContactRequest
{
    return new ContactRequest(...array_merge([
        'fullName' => 'Aminata Coulibaly',
        'phone' => '74 66 64 89',
        'email' => 'aminata@example.test',
        'subject' => 'project',
        'message' => 'Nous cherchons un logiciel de caisse pour deux boutiques.',
        'marketingOptIn' => false,
        'ip' => '196.200.10.1',
        'utm' => ['utm_source' => 'facebook'],
    ], $overrides));
}

it('crée un contact, un lead « nouveau » et les consentements', function () {
    $lead = app(CreateLeadFromContact::class)->handle(contactRequest());

    $contact = Contact::query()->sole();
    expect($contact->full_name)->toBe('Aminata Coulibaly')
        ->and($contact->phone_e164)->toBe('+22374666489')
        ->and($contact->whatsapp_e164)->toBe('+22374666489')
        ->and($contact->marketing_opt_in)->toBeFalse()
        ->and($lead->type)->toBe(LeadType::Contact)
        ->and($lead->stage)->toBe(LeadStage::New)
        ->and($lead->source)->toBe('facebook')
        ->and($lead->idempotency_key)->toHaveLength(64);

    $consents = Consent::query()->where('contact_id', $contact->id)->get()->keyBy(fn ($c) => $c->purpose->value);
    expect($consents[ConsentPurpose::RequestProcessing->value]->granted)->toBeTrue()
        ->and($consents[ConsentPurpose::Marketing->value]->granted)->toBeFalse()
        ->and($consents[ConsentPurpose::RequestProcessing->value]->text_version)->toBe('1')
        ->and($consents[ConsentPurpose::RequestProcessing->value]->ip_hash)->not->toContain('196.200');
});

it('accepte un numéro au format international', function () {
    app(CreateLeadFromContact::class)->handle(contactRequest(['phone' => '+223 97 46 28 39']));

    expect(Contact::query()->sole()->phone_e164)->toBe('+22397462839');
});

it('ne crée pas deux leads pour une double soumission identique le même jour', function () {
    $first = app(CreateLeadFromContact::class)->handle(contactRequest());
    $second = app(CreateLeadFromContact::class)->handle(contactRequest());

    expect($second->id)->toBe($first->id)
        ->and(Lead::query()->count())->toBe(1)
        ->and(Contact::query()->count())->toBe(1);
});

it('crée un nouveau lead si le message est différent', function () {
    app(CreateLeadFromContact::class)->handle(contactRequest());
    app(CreateLeadFromContact::class)->handle(contactRequest(['message' => 'Autre demande : application mobile.']));

    expect(Lead::query()->count())->toBe(2);
});

it('enregistre le consentement marketing seulement s’il est donné', function () {
    app(CreateLeadFromContact::class)->handle(contactRequest(['marketingOptIn' => true]));

    expect(Contact::query()->sole()->marketing_opt_in)->toBeTrue()
        ->and(Consent::query()->where('purpose', ConsentPurpose::Marketing->value)->sole()->granted)->toBeTrue();
});

it('passe les e-mails par la file d’envoi, sans coordonnées dans la notification interne', function () {
    app(Settings::class)->set('notify.lead_recipients', ['equipe@example.test']);

    $lead = app(CreateLeadFromContact::class)->handle(contactRequest());

    $messages = OutboundMessage::query()->orderBy('template')->get();
    expect($messages->pluck('template')->all())->toBe(['lead_ack', 'lead_new_internal'])
        ->and($messages->every(fn ($m) => $m->status === OutboundStatus::Sent))->toBeTrue()
        ->and($messages->firstWhere('template', 'lead_ack')->to)->toBe('aminata@example.test')
        ->and($messages->firstWhere('template', 'lead_new_internal')->payload)->not->toHaveKey('phone')
        ->and($lead->exists)->toBeTrue();
});

it('n’envoie pas d’accusé de réception sans e-mail', function () {
    app(CreateLeadFromContact::class)->handle(contactRequest(['email' => null]));

    expect(OutboundMessage::query()->where('template', 'lead_ack')->exists())->toBeFalse();
});
