<?php

use App\Domain\Platform\Models\Setting;
use App\Domain\Platform\Models\User;
use App\Domain\Platform\Services\Settings;
use Illuminate\Support\Facades\Cache;

it('retombe sur les valeurs par défaut de config/kh.php', function () {
    expect(app(Settings::class)->get('contact.whatsapp_primary'))->toBe('+22374666489')
        ->and(app(Settings::class)->get('inconnu', 'défaut'))->toBe('défaut');
});

it('met les paramètres en cache et invalide le cache à la sauvegarde', function () {
    $settings = app(Settings::class);
    $settings->set('contact.city', 'Bamako');

    expect($settings->get('contact.city'))->toBe('Bamako')
        ->and(Cache::has(Settings::CACHE_KEY))->toBeTrue();

    Setting::query()->where('key', 'contact.city')->firstOrFail()->update(['value' => 'Ségou']);

    expect(Cache::has(Settings::CACHE_KEY))->toBeFalse()
        ->and($settings->get('contact.city'))->toBe('Ségou');
});

it('conserve le type des valeurs JSON', function () {
    $settings = app(Settings::class);
    $settings->set('notify.lead_recipients', ['a@kilakodon.test', 'b@kilakodon.test']);
    $settings->set('ai.enabled', true);

    expect($settings->get('notify.lead_recipients'))->toBe(['a@kilakodon.test', 'b@kilakodon.test'])
        ->and($settings->get('ai.enabled'))->toBeTrue();
});

it("enregistre l'auteur de la modification", function () {
    $admin = User::factory()->admin()->create();

    $setting = app(Settings::class)->set('contact.hours', 'Lundi–samedi', $admin);

    expect($setting->updated_by)->toBe($admin->id);
});
