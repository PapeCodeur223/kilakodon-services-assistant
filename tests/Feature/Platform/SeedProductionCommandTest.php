<?php

use App\Domain\Content\Models\Page;
use App\Domain\Crm\Models\Lead;
use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\Setting;
use App\Domain\Platform\Models\User;
use App\Domain\Platform\Services\Settings;

it('installe la base de production sans aucun compte ni donnée de test, puis n’écrase plus rien', function () {
    $this->app['env'] = 'production';

    $this->artisan('kh:seed-production')->assertSuccessful()->expectsOutputToContain('Installation terminée');

    expect(Setting::query()->count())->toBeGreaterThan(5)
        ->and(Page::query()->where('slug', 'cookies')->exists())->toBeTrue()
        // Aucun compte de développement ni lead fictif en production.
        ->and(User::query()->count())->toBe(0)
        ->and(Lead::query()->count())->toBe(0);

    // Modifications faites par l'équipe dans l'admin : conservées à la mise à jour suivante.
    app(Settings::class)->set('contact.hours', 'Lundi–vendredi, 9 h–17 h');
    Page::query()->where('slug', 'cookies')->update(['title' => 'Cookies (modifié)']);
    Setting::query()->where('key', 'ai.monthly_budget_usd')->delete(); // réglage « nouveau » à ajouter

    $this->artisan('kh:seed-production')->assertSuccessful()->expectsOutputToContain('rien n’a été écrasé');

    expect(app(Settings::class)->get('contact.hours'))->toBe('Lundi–vendredi, 9 h–17 h')
        ->and(Page::query()->where('slug', 'cookies')->value('title'))->toBe('Cookies (modifié)')
        ->and(Setting::query()->where('key', 'ai.monthly_budget_usd')->exists())->toBeTrue();
});

it('refuse de tourner en local (les données de développement passent par db:seed)', function () {
    $this->app['env'] = 'local';

    $this->artisan('kh:seed-production')->assertFailed();

    expect(User::query()->where('role', UserRole::Admin)->count())->toBe(0);
});
