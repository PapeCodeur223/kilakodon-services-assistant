<?php

use App\Domain\Crm\Actions\ChangeLeadStage;
use App\Domain\Crm\Enums\ConsentPurpose;
use App\Domain\Crm\Enums\ConsentSource;
use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Crm\Models\Consent;
use App\Domain\Platform\Analytics\EventCatalog;
use App\Domain\Platform\Models\AnalyticsEvent;
use App\Domain\Platform\Models\User;
use Database\Seeders\ContentSeeder;
use Database\Seeders\SettingsSeeder;
use Spatie\Honeypot\Honeypot;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

const KH_VK = '4f1c2a9e-6b1d-4c8e-9a51-2f3b7d8e9c10';

it('ne garde que les propriétés de la liste blanche et aucune coordonnée (MES-01)', function () {
    expect(EventCatalog::filter('page_view', [
        'path' => '/solutions/erp?x=1#a',
        'referrer_host' => 'Google.com',
        'utm_source' => 'facebook',
        'utm_campaign' => 'ecrire a moi@example.com',
        'utm_term' => '+223 74 66 64 89',
        'message' => 'texte libre',
        'email' => 'moi@example.com',
    ]))->toBe(['path' => '/solutions/erp', 'referrer_host' => 'google.com', 'utm_source' => 'facebook'])
        ->and(EventCatalog::filter('page_view', ['path' => 'https://evil.test/x']))->toBe([])
        ->and(EventCatalog::filter('page_view', ['path' => '/brief/AbCdEf123/pdf']))->toBe(['path' => '/brief/*/pdf'])
        ->and(EventCatalog::filter('lab_action', ['action' => 'delete']))->toBe([])
        ->and(EventCatalog::filter('diagnostic_step_completed', ['step' => '3']))->toBe(['step' => 3])
        ->and(EventCatalog::allowsFromClient('lead_won'))->toBeFalse();
});

it('rejette toute coordonnée glissée dans un identifiant, un chemin ou un hôte (revue M1)', function () {
    expect(EventCatalog::filter('cta_click', ['cta' => '22374666489', 'path' => '/contact/awa@gmail.com']))->toBe([])
        ->and(EventCatalog::filter('cta_click', ['cta' => 'n-importe-quoi']))->toBe([])
        ->and(EventCatalog::filter('cta_click', ['cta' => 'diagnostic', 'path' => '/x/74%2066%2064%2089']))->toBe(['cta' => 'diagnostic'])
        ->and(EventCatalog::filter('page_view', ['referrer_host' => '192.168.1.10']))->toBe([])
        ->and(EventCatalog::filter('whatsapp_click', ['context' => 'solution:erp-crm-sur-mesure']))->toBe(['context' => 'solution:erp-crm-sur-mesure'])
        ->and(EventCatalog::filter('whatsapp_click', ['context' => 'tel:74666489']))->toBe([])
        ->and(EventCatalog::filter('diagnostic_started', ['entry' => 'awa@x.ml']))->toBe([]);
});

it('n’enregistre aucun événement du navigateur sans accord pour la mesure d’audience (Q-011)', function () {
    $this->postJson('/api/events', ['name' => 'page_view', 'props' => ['path' => '/']])->assertNoContent();
    $this->withCredentials()->withCookie('kh_consent', '0')->withCookie('kh_vk', KH_VK)
        ->postJson('/api/events', ['name' => 'cta_click', 'props' => ['cta' => 'diagnostic', 'path' => '/']])->assertNoContent();

    expect(AnalyticsEvent::query()->count())->toBe(0);

    // Le script de mesure ne s'active pas sans la balise posée après accord.
    $this->get('/')->assertDontSee('name="kh-analytics"', false);
    $this->withCookie('kh_consent', '1')->get('/')->assertSee('name="kh-analytics"', false);
});

it('enregistre les événements du navigateur avec la clé visiteur une fois la mesure acceptée', function () {
    $this->withCredentials()->withCookie('kh_consent', '1')->withCookie('kh_vk', KH_VK)
        ->postJson('/api/events', ['name' => 'whatsapp_click', 'props' => ['context' => 'solution:erp-crm', 'path' => '/solutions/erp-crm']])
        ->assertNoContent();
    $this->withCredentials()->withCookie('kh_consent', '1')->withCookie('kh_vk', KH_VK)
        ->postJson('/api/events', ['name' => 'page_view', 'props' => ['path' => '/', 'utm_source' => 'whatsapp']])
        ->assertNoContent();

    $event = AnalyticsEvent::query()->where('name', 'whatsapp_click')->sole();
    expect($event->visitor_key)->toBe(KH_VK)
        ->and($event->properties)->toBe(['context' => 'solution:erp-crm'])
        ->and(AnalyticsEvent::query()->where('name', 'page_view')->sole()->utm)->toBe(['utm_source' => 'whatsapp']);
});

it('refuse les événements inconnus ou réservés au serveur', function () {
    $consented = fn () => $this->withCredentials()->withCookie('kh_consent', '1');

    $consented()->postJson('/api/events', ['name' => 'lead_won'])->assertStatus(422);
    $consented()->postJson('/api/events', ['name' => 'hack', 'props' => ['x' => 1]])->assertStatus(422);
    $consented()->postJson('/api/events', ['props' => []])->assertStatus(422);

    expect(AnalyticsEvent::query()->count())->toBe(0);
});

it('affiche la bannière tant que le visiteur n’a pas choisi, jamais pré-cochée', function () {
    $this->get('/')->assertOk()
        ->assertSee('Mesure d’audience', false)
        ->assertSee('value="refuse"', false)
        ->assertSee('value="accept"', false)
        ->assertDontSee('checked', false);

    $this->withCookie('kh_consent', '0')->get('/')->assertOk()->assertDontSee('data-cookie-banner', false);
});

it('enregistre l’accord, crée la clé visiteur et revient sur la page d’origine', function () {
    $response = $this->post('/cookies/preferences', ['analytics' => 'accept', 'return' => '/solutions'])
        ->assertRedirect('/solutions')
        ->assertCookie('kh_consent', '1');

    $key = $response->getCookie('kh_vk')?->getValue();
    expect($key)->not->toBeNull();

    $consent = Consent::query()->sole();
    expect($consent->purpose)->toBe(ConsentPurpose::Analytics)
        ->and($consent->granted)->toBeTrue()
        ->and($consent->source)->toBe(ConsentSource::CookieBanner)
        ->and($consent->visitor_key)->toBe($key)
        ->and($consent->ip_hash)->not->toBeNull()->not->toContain('127.0.0.1');
});

it('trace le retrait sur la même clé et supprime le cookie visiteur', function () {
    $this->withCookie('kh_consent', '1')->withCookie('kh_vk', KH_VK)
        ->post('/cookies/preferences', ['analytics' => 'refuse', 'return' => '/cookies#vos-choix'])
        ->assertRedirect('/cookies#vos-choix')
        ->assertCookie('kh_consent', '0')
        ->assertCookieExpired('kh_vk');

    $consent = Consent::query()->sole();
    expect($consent->granted)->toBeFalse()->and($consent->visitor_key)->toBe(KH_VK);
});

it('ne redirige jamais vers un autre site', function () {
    foreach (['https://evil.test', '//evil.test', '/\\evil.test', "/\t/evil.test", "/\n/evil.test"] as $return) {
        $this->post('/cookies/preferences', ['analytics' => 'refuse', 'return' => $return])->assertRedirect(route('home'));
    }
});

it('permet de revoir son choix sur la page Cookies', function () {
    $this->seed(ContentSeeder::class);

    $this->get('/cookies')->assertOk()
        ->assertSee('Vos choix')
        ->assertSee('Aucun choix enregistré : la mesure d’audience est désactivée.', false);
});

it('écrit les événements serveur des leads sans coordonnée', function () {
    $honeypot = app(Honeypot::class);
    $trap = [$honeypot->nameFieldName() => '', $honeypot->validFromFieldName() => $honeypot->encryptedValidFrom()];
    $this->travel(5)->seconds();

    $this->withCookie('kh_consent', '1')->withCookie('kh_vk', KH_VK)->post('/contact', [
        'name' => 'Awa Traoré',
        'phone' => '74 66 64 89',
        'email' => 'awa@example.test',
        'subject' => 'project',
        'message' => 'Je veux un logiciel de caisse pour ma boutique.',
        'consent_processing' => '1',
        ...$trap,
    ])->assertRedirect();

    expect(AnalyticsEvent::query()->pluck('name')->all())->toContain('lead_created', 'contact_submitted');
    $created = AnalyticsEvent::query()->where('name', 'lead_created')->sole();
    expect($created->properties)->toBe(['type' => 'contact', 'source' => 'organic'])
        ->and($created->visitor_key)->toBeNull()
        ->and(json_encode(AnalyticsEvent::query()->get()->toArray()))->not->toContain('Awa')->not->toContain('awa@')->not->toContain('6664');
});

it('écrit lead_stage_changed puis lead_won', function () {
    $admin = User::factory()->admin()->create();
    $lead = makeLead();
    $change = app(ChangeLeadStage::class);

    foreach ([LeadStage::Qualified, LeadStage::Meeting, LeadStage::Proposal, LeadStage::Won] as $stage) {
        $lead = $change->handle($lead, $stage, $admin);
    }

    expect(AnalyticsEvent::query()->where('name', 'lead_stage_changed')->count())->toBe(4)
        ->and(AnalyticsEvent::query()->where('name', 'lead_won')->count())->toBe(1);
});

it('mesure le parcours de diagnostic côté serveur (fonctionne sans JavaScript)', function () {
    completedDiagnostic();

    expect(AnalyticsEvent::query()->where('name', 'diagnostic_started')->sole()->properties)->toBe(['entry' => 'home'])
        ->and(AnalyticsEvent::query()->where('name', 'diagnostic_completed')->sole()->properties)->toHaveKeys(['template_version', 'maturity_level']);
});
