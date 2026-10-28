<?php

use App\Domain\Platform\Models\Setting;
use App\Domain\Platform\Models\User;
use Database\Seeders\DevSeeder;
use Database\Seeders\SettingsSeeder;

it('crée les trois utilisateurs de développement, un par rôle', function () {
    $this->seed(DevSeeder::class);

    expect(User::query()->pluck('role')->map->value->sort()->values()->all())
        ->toBe(['admin', 'editor', 'sales']);
});

it('seed les paramètres sans écraser une valeur existante', function () {
    Setting::query()->create(['key' => 'contact.hours', 'value' => 'Valeur saisie']);

    $this->seed(SettingsSeeder::class);

    expect(Setting::query()->where('key', 'contact.hours')->value('value'))->toBe('Valeur saisie')
        ->and(Setting::query()->count())->toBe(count(config('kh.settings')));
});

it("n'exécute pas le DevSeeder hors environnement local", function () {
    $this->seed();

    expect(User::query()->count())->toBe(0);
});

it('restaure le secret MFA de l\'admin de développement après une réinitialisation', function () {
    config(['kh.dev_admin_mfa_secret' => 'JBSWY3DPEHPK3PXP']);

    $this->seed(DevSeeder::class);

    $admin = User::query()->where('email', 'admin@kilakodon.test')->sole();
    expect($admin->getAppAuthenticationSecret())->toBe('JBSWY3DPEHPK3PXP')
        ->and($admin->getRawOriginal('app_authentication_secret'))->not->toBe('JBSWY3DPEHPK3PXP');
});
