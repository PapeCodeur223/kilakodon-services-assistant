<?php

use App\Domain\Brief\Actions\RequestBrief;
use App\Domain\Crm\Data\ProspectDetails;
use App\Domain\Diagnostic\Enums\TemplateStatus;
use App\Domain\Diagnostic\Models\DiagnosticTemplate;
use App\Domain\Platform\Models\User;
use App\Filament\Resources\Briefs\Pages\ViewBrief;
use App\Filament\Resources\DiagnosticTemplates\Pages\EditDiagnosticTemplate;
use App\Filament\Resources\DiagnosticTemplates\Pages\ViewDiagnosticTemplate;
use App\Filament\Resources\DiagnosticTemplates\RelationManagers\QuestionsRelationManager;
use Livewire\Livewire;

beforeEach(fn () => seedDiagnosticTemplate());

it('réserve les questionnaires à l\'admin ; sessions et briefs à l\'admin et au commercial', function () {
    $template = DiagnosticTemplate::query()->sole();
    $admin = User::factory()->admin()->withMfa()->create();

    $this->actingAs($admin)->get('/admin/diagnostic-templates')->assertOk()->assertSee('Diagnostic v1');
    $this->actingAs($admin)->get('/admin/diagnostic-templates/'.$template->id)->assertOk()->assertSee('Questions');
    $this->actingAs($admin)->get('/admin/diagnostic-sessions')->assertOk();
    $this->actingAs($admin)->get('/admin/briefs')->assertOk();

    $sales = User::factory()->sales()->create();
    $this->actingAs($sales)->get('/admin/diagnostic-templates')->assertForbidden();
    $this->actingAs($sales)->get('/admin/diagnostic-sessions')->assertOk();
    $this->actingAs($sales)->get('/admin/briefs')->assertOk();

    $editor = User::factory()->editor()->create();
    $this->actingAs($editor)->get('/admin/briefs')->assertForbidden();
    $this->actingAs($editor)->get('/admin/diagnostic-sessions')->assertForbidden();
});

it('interdit l\'édition d\'un questionnaire actif et propose la duplication', function () {
    $template = DiagnosticTemplate::query()->sole();
    $this->actingAs(User::factory()->admin()->withMfa()->create());

    $this->get('/admin/diagnostic-templates/'.$template->id.'/edit')->assertForbidden();

    Livewire::test(QuestionsRelationManager::class, ['ownerRecord' => $template, 'pageClass' => ViewDiagnosticTemplate::class])
        ->assertTableActionHidden('edit', $template->questions()->first());

    Livewire::test(ViewDiagnosticTemplate::class, ['record' => $template->getRouteKey()])
        ->assertActionHidden('activate')
        ->callAction('duplicate');

    $copy = DiagnosticTemplate::query()->where('version', 2)->sole();
    expect($copy->status)->toBe(TemplateStatus::Draft);

    Livewire::test(EditDiagnosticTemplate::class, ['record' => $copy->getRouteKey()])
        ->callAction('activate')
        ->assertNotified('Version 2 activée');

    expect($copy->refresh()->status)->toBe(TemplateStatus::Active)
        ->and($template->refresh()->status)->toBe(TemplateStatus::Retired);
});

it('affiche un brief et permet au commercial de révoquer son lien', function () {
    ['session' => $session] = completedDiagnostic(['sector' => 'retail', 'pains' => ['stock_gaps']]);
    ['brief' => $brief] = app(RequestBrief::class)->handle($session, new ProspectDetails('Awa Diarra', '74 66 64 89', company: 'Boutique Awa'));

    $this->actingAs(User::factory()->sales()->create());

    $this->get('/admin/briefs/'.$brief->id)
        ->assertOk()
        ->assertSee($brief->reference)
        ->assertSee('Déclaré par le prospect')
        ->assertSee('Recommandé par Kilakodon (règles)')
        ->assertSee('Gestion de stock &amp; caisse', false);

    Livewire::test(ViewBrief::class, ['record' => $brief->getRouteKey()])
        ->callAction('revoke')
        ->assertNotified('Lien révoqué');

    expect($brief->refresh()->isRevoked())->toBeTrue();
});
