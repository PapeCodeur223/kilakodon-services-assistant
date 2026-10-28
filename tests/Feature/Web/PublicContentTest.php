<?php

use App\Domain\Content\Actions\PublishContent;
use App\Domain\Content\Models\Faq;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Models\Sector;
use App\Domain\Content\Models\Service;
use App\Domain\Platform\Models\User;
use Database\Factories\Content\CaseStudyFactory;
use Database\Factories\Content\ServiceFactory;
use Database\Seeders\ContentSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed([SettingsSeeder::class, ContentSeeder::class]);
});

function publish(object $content): void
{
    app(PublishContent::class)->handle($content, User::factory()->admin()->create());
}

it('rend toutes les pages publiques principales sans lien vers « # »', function (string $path) {
    $this->get($path)
        ->assertOk()
        ->assertDontSee('href="#"', false)
        ->assertSee('<link rel="canonical"', false)
        ->assertSee('property="og:title"', false);
})->with(['/', '/solutions', '/secteurs/commerce-distribution', '/realisations', '/ressources', '/contact']);

it('rend l’accueil complet sans JavaScript requis pour le contenu', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<em>faits pour elle</em>', false)
        ->assertSee('Conçu pour la façon dont on')
        ->assertSee('Savoir quoi construire avant de')
        ->assertSee('Combien coûte un logiciel sur mesure ?')
        ->assertSee('"@type":"FAQPage"', false)
        ->assertSee('"LocalBusiness"', false);
});

it('masque au public les éléments « [À VALIDER] » de l’accueil', function () {
    $this->get('/')
        ->assertDontSee('VALIDER')
        ->assertDontSee('Mobile Money intégré')
        ->assertDontSee('Vos données vous appartiennent');
});

it('masque la section Réalisations tant qu’aucune n’est publiée', function () {
    $this->get('/')->assertDontSee('id="realisations"', false);

    publish(CaseStudyFactory::new()->authorized()->create(['title' => 'Caisse pour une chaîne de boutiques']));

    $this->get('/')->assertSee('id="realisations"', false)->assertSee('Caisse pour une chaîne de boutiques');
});

it('renvoie 404 au public pour un brouillon et le montre à l’équipe en prévisualisation', function () {
    $service = Service::query()->where('slug', 'stocks-facturation-caisse')->sole();

    $this->get('/solutions/stocks-facturation-caisse')->assertNotFound();

    $this->actingAs(User::factory()->editor()->create())
        ->get('/solutions/stocks-facturation-caisse')
        ->assertOk()
        ->assertSee('Prévisualisation équipe')
        ->assertSee('noindex', false)
        ->assertSee($service->title);
});

it('n’autorise pas la prévisualisation pour un commercial', function () {
    $this->actingAs(User::factory()->sales()->create())
        ->get('/solutions/stocks-facturation-caisse')
        ->assertNotFound();
});

it('affiche une solution publiée avec liens diagnostic et WhatsApp et JSON-LD Service', function () {
    $service = ServiceFactory::new()->create(['slug' => 'ma-solution', 'title' => 'Ma solution test']);
    publish($service);

    $this->get('/solutions/ma-solution')
        ->assertOk()
        ->assertSee('/diagnostic?entry=solution%3Ama-solution', false)
        ->assertSee('wa.me/22374666489?text=', false)
        ->assertSee(rawurlencode('« Ma solution test »'), false)
        ->assertSee('"@type":"Service"', false)
        ->assertSee('Sur devis après diagnostic');
});

it('affiche un secteur avec le diagnostic pré-rempli', function () {
    $this->get('/secteurs/commerce-distribution')
        ->assertOk()
        ->assertSee('secteur=retail', false)
        ->assertSee('Écarts de stock inexpliqués');
});

it('n’affiche jamais un résultat non vérifié ni une citation non autorisée', function () {
    $caseStudy = CaseStudyFactory::new()->authorized()->create([
        'slug' => 'cas-test',
        'results' => 'Résultat chiffré non vérifié',
        'results_verified' => false,
    ]);
    publish($caseStudy);

    $this->get('/realisations/cas-test')->assertOk()->assertDontSee('Résultat chiffré non vérifié');
});

it('affiche les pages légales publiées avec un sommaire ancré', function () {
    $page = Page::query()->where('slug', 'cookies')->sole();
    $page->update(['body' => "## Cookies nécessaires\n\nTexte.\n\n## Publicité\n\nAucun."]);
    publish($page);

    $this->get('/cookies')
        ->assertOk()
        ->assertSee('href="#cookies-necessaires"', false)
        ->assertSee('id="cookies-necessaires"', false);
});

it('ne fait apparaître dans la navigation que les contenus visibles', function () {
    $this->get('/')
        ->assertDontSee('/solutions/stocks-facturation-caisse', false)
        ->assertSee('/secteurs/commerce-distribution', false)
        ->assertDontSee('href="'.url('/mentions-legales').'"', false);
});

it('échappe le HTML saisi dans un contenu Markdown', function () {
    $page = Page::query()->where('slug', 'cookies')->sole();
    $page->update(['body' => "Texte <script>alert('x')</script> [lien](javascript:alert(1))"]);
    publish($page);

    $this->get('/cookies')
        ->assertDontSee("<script>alert('x')</script>", false)
        ->assertDontSee('href="javascript:', false);
});

it('ne publie que 6 FAQ au plus sur l’accueil', function () {
    expect(Faq::query()->published()->where('on_home', true)->count())->toBeLessThanOrEqual(6)
        ->and(Sector::query()->published()->count())->toBe(6);
});
