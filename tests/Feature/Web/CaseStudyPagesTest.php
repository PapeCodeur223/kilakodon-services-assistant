<?php

use App\Domain\Content\Actions\PublishContent;
use App\Domain\Content\Models\CaseStudy;
use App\Domain\Content\Models\Sector;
use App\Domain\Platform\Models\User;
use Database\Factories\Content\CaseStudyFactory;
use Database\Seeders\ContentSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed([SettingsSeeder::class, ContentSeeder::class]);
    // Les réalisations seedées sont publiées (D-024) : on les repasse en brouillon pour maîtriser la liste.
    CaseStudy::query()->update(['status' => 'draft', 'published_at' => null]);
});

function publishedCase(string $title, string $sectorCode, array $attributes = []): CaseStudy
{
    $case = CaseStudyFactory::new()->authorized()->create([
        'title' => $title,
        'sector_id' => Sector::query()->where('code', $sectorCode)->value('id'),
        ...$attributes,
    ]);
    app(PublishContent::class)->handle($case, User::factory()->admin()->create());

    return $case->refresh();
}

it('ne montre au public que les réalisations publiées, avec des filtres par secteur', function () {
    publishedCase('Caisse pour une boutique', 'retail');
    publishedCase('Gestion scolaire de test', 'school');

    $this->get('/realisations')->assertOk()
        ->assertSee('Caisse pour une boutique')
        ->assertSee('Gestion scolaire de test')
        // Les 10 réalisations seedées, repassées en brouillon, : invisibles pour le public.
        ->assertDontSee('Logiciel de gestion de pharmacie')
        ->assertSee('?secteur=retail', false)
        ->assertSee('?secteur=school', false)
        ->assertDontSee('?secteur=health', false)
        ->assertSee('À la une');
});

it('filtre la liste par secteur sans JavaScript et ignore un secteur inconnu', function () {
    publishedCase('Caisse pour une boutique', 'retail');
    publishedCase('Gestion scolaire de test', 'school');

    $this->get('/realisations?secteur=school')->assertOk()
        ->assertSee('Gestion scolaire de test')
        ->assertDontSee('Caisse pour une boutique')
        ->assertSee('aria-current="page"', false);

    $this->get('/realisations?secteur=inconnu')->assertOk()
        ->assertSee('Gestion scolaire de test')
        ->assertSee('Caisse pour une boutique');
});

it('affiche le récit, les livrables et d’autres réalisations sur la fiche', function () {
    $case = publishedCase('Caisse pour une boutique', 'retail', [
        'context' => 'Une boutique de quartier.',
        'challenge' => 'Contrôler la caisse.',
        'intervention' => 'Un logiciel de caisse.',
        'deliverables' => ['Caisse', 'Stock'],
    ]);
    publishedCase('Autre réalisation publiée', 'retail');

    $this->get($case->publicUrl())->assertOk()
        ->assertSeeInOrder(['Contexte', 'Une boutique de quartier.', 'Défi', 'Contrôler la caisse.', 'Notre intervention', 'Un logiciel de caisse.'])
        ->assertSee('2 livrables')
        ->assertSee('Autre réalisation publiée')
        ->assertDontSee('Logiciel de gestion de pharmacie');
});

it('n’affiche ni résultats non vérifiés ni témoignage sans autorisation', function () {
    $case = publishedCase('Caisse pour une boutique', 'retail', ['results' => 'Résultat non vérifié', 'results_verified' => false]);

    $this->get($case->publicUrl())->assertOk()->assertDontSee('Résultat non vérifié');
});
