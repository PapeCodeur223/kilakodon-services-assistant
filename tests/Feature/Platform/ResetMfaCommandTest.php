<?php

use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\AuditLog;
use App\Domain\Platform\Models\User;

it('réinitialise la double authentification d\'un utilisateur et le journalise', function () {
    $admin = User::factory()->admin()->withMfa()->create(['email' => 'direction@example.test']);
    $admin->saveAppAuthenticationRecoveryCodes(['code-1', 'code-2']);

    $this->artisan('kh:mfa-reset', ['email' => 'Direction@Example.test', '--force' => true])
        ->expectsOutputToContain('Double authentification réinitialisée')
        ->assertSuccessful();

    $admin->refresh();
    expect($admin->getAppAuthenticationSecret())->toBeNull()
        ->and($admin->getAppAuthenticationRecoveryCodes())->toBeNull()
        ->and(AuditLog::query()->where('action', AuditAction::MultiFactorReset)->count())->toBe(1);
});

it('demande une confirmation et n\'agit pas si elle est refusée', function () {
    $admin = User::factory()->admin()->withMfa()->create(['email' => 'direction@example.test']);

    $this->artisan('kh:mfa-reset', ['email' => 'direction@example.test'])
        ->expectsConfirmation('Réinitialiser la double authentification de direction@example.test ?', 'no')
        ->assertFailed();

    expect($admin->refresh()->getAppAuthenticationSecret())->not->toBeNull();
});

it('signale un compte inconnu ou sans double authentification', function () {
    User::factory()->sales()->create(['email' => 'commercial@example.test']);

    $this->artisan('kh:mfa-reset', ['email' => 'inconnu@example.test', '--force' => true])->assertFailed();
    $this->artisan('kh:mfa-reset', ['email' => 'commercial@example.test', '--force' => true])
        ->expectsOutputToContain('rien à réinitialiser')
        ->assertSuccessful();
});

it('redemande la configuration de la double authentification à l\'admin après réinitialisation', function () {
    $admin = User::factory()->admin()->withMfa()->create(['email' => 'direction@example.test']);
    $this->artisan('kh:mfa-reset', ['email' => 'direction@example.test', '--force' => true]);

    $this->actingAs($admin->refresh())->get('/admin')->assertRedirectContains('multi-factor-authentication');
});
