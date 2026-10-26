<?php

use App\Domain\Platform\Models\User;

it('redirige un visiteur anonyme vers la connexion', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('exige la configuration de la MFA pour un admin qui ne l\'a pas activée', function () {
    $this->actingAs(User::factory()->admin()->create());

    $response = $this->get('/admin');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('multi-factor-authentication');
});

it('laisse entrer un admin dont la MFA est active', function () {
    $this->actingAs(User::factory()->admin()->withMfa()->create());

    $this->get('/admin')
        ->assertOk()
        ->assertSee('Paramètres')
        ->assertSee('Utilisateurs')
        ->assertSee('Journal');
});

it('n\'exige pas la MFA pour les rôles sales et editor', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create());

    $this->get('/admin')->assertOk();
})->with(['sales', 'editor']);

it('masque Paramètres, Utilisateurs et Journal aux rôles non admin', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create());

    $this->get('/admin')
        ->assertOk()
        ->assertDontSee('Paramètres')
        ->assertDontSee('Utilisateurs')
        ->assertDontSee('Journal');
})->with(['sales', 'editor']);

it('refuse l\'accès direct aux écrans réservés aux admins', function (string $role, string $path) {
    $this->actingAs(User::factory()->{$role}()->create());

    $this->get($path)->assertForbidden();
})->with(['sales', 'editor'])->with(['/admin/parametres', '/admin/users', '/admin/audit-logs']);

it('refuse un compte désactivé', function () {
    $this->actingAs(User::factory()->admin()->withMfa()->inactive()->create());

    $this->get('/admin')->assertForbidden();
});
