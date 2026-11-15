<?php

use App\Domain\Content\Models\Service;
use App\Domain\Content\Services\PendingValidationReport;
use App\Domain\Platform\Models\User;
use App\Domain\Platform\Services\Settings;
use App\Filament\Pages\PendingValidations;
use App\Filament\Resources\Services\ServiceResource;
use Database\Seeders\ContentSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(fn () => $this->seed([SettingsSeeder::class, ContentSeeder::class]));

it('ouvre la page « À valider » à l\'admin et à l\'éditeur, pas au commercial', function () {
    $this->actingAs(User::factory()->editor()->create())->get('/admin/a-valider')->assertOk();
    $this->actingAs(User::factory()->admin()->withMfa()->create())->get('/admin/a-valider')->assertOk();
    $this->actingAs(User::factory()->sales()->create())->get('/admin/a-valider')->assertForbidden();
});

it('liste les mêmes points que la commande d\'audit, avec le texte exact et un lien « Corriger »', function () {
    $service = Service::query()->where('slug', 'erp-crm-sur-mesure')->sole();
    $count = app(PendingValidationReport::class)->count();

    $this->actingAs(User::factory()->editor()->create())->get('/admin/a-valider')
        ->assertOk()
        ->assertSee($count.' information(s) attendent votre confirmation')
        ->assertSee($service->title)
        ->assertSee('Délai indicatif')
        ->assertSee('Premier module en 6 à 10 semaines [À VALIDER]')
        ->assertSee(ServiceResource::getUrl('edit', ['record' => $service]), false)
        // Les textes de l'accueil sont tous validés depuis D-017 et D-018.
        ->assertDontSee('Page d&#039;accueil', false);

    expect($count)->toBeGreaterThan(0);
});

it('signale un paramètre non confirmé avec un lien vers les paramètres pour l\'admin', function () {
    app(Settings::class)->set('contact.hours', 'Horaires [À VALIDER]');

    $this->actingAs(User::factory()->admin()->withMfa()->create())->get('/admin/a-valider')
        ->assertSee('contact.hours')
        ->assertSee('/admin/parametres', false);
});

it('signale le paramètre à l\'éditeur sans lien vers les paramètres, qui lui sont interdits', function () {
    app(Settings::class)->set('contact.hours', 'Horaires [À VALIDER]');

    $this->actingAs(User::factory()->editor()->create())->get('/admin/a-valider')
        ->assertSee('contact.hours')
        ->assertDontSee('/admin/parametres', false);
});

it('rend des libellés de champs lisibles', function () {
    $report = app(PendingValidationReport::class);

    expect($report->fieldLabel('features.2.title'))->toBe('Fonctionnalité n° 3')
        ->and($report->fieldLabel('duration_hint'))->toBe('Délai indicatif')
        ->and($report->fieldLabel('faq.1.a'))->toBe('FAQ de la fiche n° 2');
});

it('affiche un badge du nombre de points dans le menu', function () {
    $count = app(PendingValidationReport::class)->count();

    expect(PendingValidations::getNavigationBadge())->toBe((string) $count);
});
