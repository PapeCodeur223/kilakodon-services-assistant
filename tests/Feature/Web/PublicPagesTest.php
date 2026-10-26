<?php

it("rend l'accueil avec le header et le pied de page", function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Aller au contenu')
        ->assertSee('Diagnostic gratuit')
        ->assertSee('<em>faits pour elle</em>', false)
        ->assertSee('Gérer mes données')
        ->assertSee('+223 74 66 64 89')
        ->assertDontSee('href="#"', false);
});

it('sert le styleguide uniquement en local', function () {
    $this->get('/_styleguide')->assertNotFound();

    app()->detectEnvironment(fn () => 'local');

    $this->get('/_styleguide')
        ->assertOk()
        ->assertSee('noindex', false)
        ->assertSee('Styleguide');
});

it('affiche une page 404 stylée avec les liens utiles', function () {
    $this->get('/page-qui-n-existe-pas')
        ->assertNotFound()
        ->assertSee("Cette page n'existe pas", false)
        ->assertSee(url('/solutions'))
        ->assertSee('noindex', false);
});

it('affiche une page 500 sans dépendre de la base', function () {
    $html = view('errors.500')->render();

    expect($html)->toContain('Erreur 500')->toContain('wa.me/22374666489');
});

it('ajoute les en-têtes de sécurité sur les pages publiques', function () {
    $response = $this->get('/');

    $response->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

    $csp = (string) $response->headers->get('Content-Security-Policy');
    expect($csp)->toContain("script-src 'self' 'nonce-")
        ->not->toContain('unsafe-eval')
        ->toContain("frame-ancestors 'none'")
        ->and($response->headers->get('Permissions-Policy'))->toContain('camera=()');
});

it('applique aussi les en-têtes de sécurité au back-office', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'DENY');
});

it("pose le nonce CSP sur le script d'amorçage", function () {
    $response = $this->get('/');
    preg_match("/'nonce-([^']+)'/", (string) $response->headers->get('Content-Security-Policy'), $m);

    expect($m[1] ?? null)->not->toBeNull();
    $response->assertSee('nonce="'.$m[1].'"', false);
});
