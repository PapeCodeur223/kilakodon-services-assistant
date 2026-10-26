<?php

use App\Domain\Content\Actions\PublishContent;
use App\Domain\Platform\Models\User;
use Database\Factories\Content\ServiceFactory;

it('redirige les anciennes URL en 301', function (string $from, string $to) {
    $this->get($from)->assertStatus(301)->assertRedirect($to);
})->with([
    ['/index.php', '/'],
    ['/services.php', '/solutions'],
    ['/shop.php', '/demo-lab'],
    ['/portofolio.php', '/realisations'],
    ['/contact.php', '/contact'],
    ['/pages/produit_detail.php?id=1', '/secteurs/ecoles-formation'],
    ['/pages/produit_detail.php?id=2', '/secteurs/boulangeries-restauration'],
    ['/pages/produit_detail.php?id=99', '/solutions'],
    ['/client/connexion.php', '/contact'],
    ['/client/mon_compte.php', '/contact'],
]);

it('liste uniquement les contenus publiés dans le sitemap', function () {
    $draft = ServiceFactory::new()->create(['slug' => 'brouillon-secret']);
    $published = ServiceFactory::new()->create(['slug' => 'solution-publiee']);
    app(PublishContent::class)->handle($published, User::factory()->admin()->create());

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('/solutions/solution-publiee', false)
        ->assertDontSee('brouillon-secret', false);
});

it('ferme le site aux robots hors production et protège les espaces privés en production', function () {
    $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');

    app()->detectEnvironment(fn () => 'production');
    $this->get('/robots.txt')
        ->assertSee('Disallow: /admin')
        ->assertSee('Disallow: /brief/')
        ->assertSee('Sitemap: '.url('/sitemap.xml'));
});

it('ajoute X-Robots-Tag noindex sur les espaces privés', function () {
    $this->get('/admin/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    expect($this->get('/')->headers->has('X-Robots-Tag'))->toBeFalse();
});

it('mémorise la source UTM de la page d’arrivée', function () {
    $this->get('/?utm_source=facebook&utm_campaign=lancement')->assertSessionHas('kh.utm', ['utm_source' => 'facebook', 'utm_campaign' => 'lancement']);
});
