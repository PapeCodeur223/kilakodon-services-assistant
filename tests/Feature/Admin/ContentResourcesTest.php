<?php

use App\Domain\Content\Actions\PublishContent;
use App\Domain\Content\Enums\AuthorizationStatus;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\Service;
use App\Domain\Crm\Models\Lead;
use App\Domain\Platform\Models\User;
use App\Filament\Resources\CaseStudies\Pages\ListCaseStudies;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use Database\Factories\Content\CaseStudyFactory;
use Database\Factories\Content\ServiceFactory;
use Livewire\Livewire;

it('ouvre les écrans de contenu aux admins et éditeurs, pas aux commerciaux', function (string $path) {
    $this->actingAs(User::factory()->editor()->create())->get($path)->assertOk();
    $this->actingAs(User::factory()->sales()->create())->get($path)->assertForbidden();
})->with(['/admin/services', '/admin/sectors', '/admin/case-studies', '/admin/articles', '/admin/faqs', '/admin/pages']);

it('ouvre la liste des leads aux admins et commerciaux, pas aux éditeurs', function () {
    $this->actingAs(User::factory()->sales()->create())->get('/admin/leads')->assertOk();
    $this->actingAs(User::factory()->editor()->create())->get('/admin/leads')->assertForbidden();
});

it('permet à un admin de publier depuis la liste', function () {
    $this->actingAs(User::factory()->admin()->withMfa()->create());
    $service = ServiceFactory::new()->create();

    Livewire::test(ListServices::class)
        ->callTableAction('publish', $service)
        ->assertNotified('Contenu publié');

    expect($service->fresh()->status)->toBe(ContentStatus::Published);
});

it('ne propose pas l’action Publier à un éditeur', function () {
    $this->actingAs(User::factory()->editor()->create());
    $service = ServiceFactory::new()->create();

    Livewire::test(ListServices::class)->assertTableActionHidden('publish', $service);
});

it('refuse de publier un contenu [À VALIDER] et l’explique', function () {
    $this->actingAs(User::factory()->admin()->withMfa()->create());
    $service = ServiceFactory::new()->create(['duration_hint' => '3 semaines [À VALIDER]']);

    Livewire::test(ListServices::class)
        ->callTableAction('publish', $service)
        ->assertNotified('Action impossible');

    expect($service->fresh()->status)->toBe(ContentStatus::Draft);
});

it('enregistre une modification et crée une révision', function () {
    $this->actingAs(User::factory()->editor()->create());
    $service = ServiceFactory::new()->create();

    Livewire::test(EditService::class, ['record' => $service->getRouteKey()])
        ->fillForm(['tagline' => 'Nouvelle accroche'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Service::query()->find($service->id)->tagline)->toBe('Nouvelle accroche')
        ->and($service->revisions()->count())->toBe(2);
});

it('interdit à un éditeur d’ouvrir l’édition d’un contenu publié', function () {
    $service = ServiceFactory::new()->create();
    app(PublishContent::class)->handle($service, User::factory()->admin()->create());

    $this->actingAs(User::factory()->editor()->create())
        ->get('/admin/services/'.$service->id.'/edit')
        ->assertForbidden();
});

it('permet à l’admin d’attester l’autorisation d’une réalisation avec une preuve', function () {
    $this->actingAs(User::factory()->admin()->withMfa()->create());
    $caseStudy = CaseStudyFactory::new()->create();

    Livewire::test(ListCaseStudies::class)
        ->callTableAction('attest', $caseStudy, ['authorization_status' => 'granted', 'authorization_note' => 'Accord écrit par e-mail du 20/10/2026, M. Traoré'])
        ->assertHasNoTableActionErrors();

    expect($caseStudy->fresh()->authorization_status)->toBe(AuthorizationStatus::Granted);
});

it('liste les leads créés par le formulaire de contact', function () {
    $this->actingAs(User::factory()->sales()->create());
    expect(Lead::query()->count())->toBe(0);

    $this->get('/admin/leads')->assertOk()->assertSee('Leads');
});
