<?php

use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\User;

const PASSWORD_QUESTION = 'Mot de passe (12 caractères minimum, lettres et chiffres)';
const CONFIRM_QUESTION = 'Confirmez le mot de passe';

it('crée un administrateur', function () {
    $this->artisan('kh:make-admin', ['--name' => 'Moussa Diarra', '--email' => 'moussa@kilakodon.test'])
        ->expectsQuestion(PASSWORD_QUESTION, 'un-mot-de-passe-solide-7')
        ->expectsQuestion(CONFIRM_QUESTION, 'un-mot-de-passe-solide-7')
        ->assertSuccessful();

    $user = User::query()->where('email', 'moussa@kilakodon.test')->firstOrFail();
    expect($user->role)->toBe(UserRole::Admin)
        ->and($user->is_active)->toBeTrue();
});

it('refuse un mot de passe trop court', function () {
    $this->artisan('kh:make-admin', ['--name' => 'Test', '--email' => 'test@kilakodon.test'])
        ->expectsQuestion(PASSWORD_QUESTION, 'court1')
        ->expectsQuestion(CONFIRM_QUESTION, 'court1')
        ->assertFailed();

    expect(User::query()->where('email', 'test@kilakodon.test')->exists())->toBeFalse();
});

it('refuse des mots de passe différents', function () {
    $this->artisan('kh:make-admin', ['--name' => 'Test', '--email' => 'test@kilakodon.test'])
        ->expectsQuestion(PASSWORD_QUESTION, 'un-mot-de-passe-solide-7')
        ->expectsQuestion(CONFIRM_QUESTION, 'un-autre-mot-de-passe-8')
        ->assertFailed();
});

it('refuse un e-mail déjà utilisé', function () {
    User::factory()->create(['email' => 'pris@kilakodon.test']);

    $this->artisan('kh:make-admin', ['--name' => 'Test', '--email' => 'pris@kilakodon.test'])
        ->expectsQuestion(PASSWORD_QUESTION, 'un-mot-de-passe-solide-7')
        ->expectsQuestion(CONFIRM_QUESTION, 'un-mot-de-passe-solide-7')
        ->assertFailed();
});
