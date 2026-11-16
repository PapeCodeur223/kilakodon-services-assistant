<?php

use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\Faq;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Models\Sector;
use App\Domain\Content\Models\Service;
use App\Domain\Content\Support\PendingValidation;
use App\Domain\Platform\Services\Settings;
use Database\Seeders\ContentSeeder;
use Database\Seeders\SettingsSeeder;

it('seed les contenus validés : brouillon s’ils contiennent [À VALIDER], publiés sinon', function () {
    $this->seed(ContentSeeder::class);

    expect(Service::query()->count())->toBe(6)
        ->and(Sector::query()->count())->toBe(6)
        ->and(Faq::query()->count())->toBe(12)
        ->and(Page::query()->count())->toBe(5)
        ->and(Article::query()->count())->toBe(4);

    foreach ([Service::class, Sector::class, Faq::class, Page::class] as $model) {
        foreach ($model::all() as $content) {
            $pending = PendingValidation::has($content->only($content->contentFields()));
            expect($content->status)->toBe($pending ? ContentStatus::Draft : ContentStatus::Published);
        }
    }

    expect(Article::query()->where('status', '!=', ContentStatus::Draft->value)->exists())->toBeFalse();
});

it('est idempotent et n’écrase pas une modification', function () {
    $this->seed(ContentSeeder::class);
    Sector::query()->where('code', 'retail')->sole()->update(['intro' => 'Intro modifiée']);

    $this->seed(ContentSeeder::class);

    expect(Sector::query()->count())->toBe(6)
        ->and(Sector::query()->where('code', 'retail')->value('intro'))->toBe('Intro modifiée');
});

it('signale les [À VALIDER] restants et échoue tant qu’il en reste', function () {
    $this->seed([SettingsSeeder::class, ContentSeeder::class]);
    // Un paramètre non confirmé doit aussi être signalé (les horaires par défaut sont validés, D-015).
    app(Settings::class)->set('contact.hours', 'Horaires [À VALIDER]');

    $this->artisan('kh:content-audit')
        ->expectsOutputToContain('contact.hours')
        ->doesntExpectOutputToContain('resources/content/home.php')
        ->assertFailed();
});

it('inclut les brouillons avec --all', function () {
    $this->seed(ContentSeeder::class);
    Service::query()->where('slug', 'stocks-facturation-caisse')
        ->update(['status' => ContentStatus::Draft->value, 'published_at' => null, 'duration_hint' => 'À préciser [À VALIDER]']);

    $this->artisan('kh:content-audit --all')
        ->expectsOutputToContain('stocks-facturation-caisse')
        ->assertFailed();
});
