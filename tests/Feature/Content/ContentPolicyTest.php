<?php

use App\Domain\Content\Actions\PublishContent;
use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\CaseStudy;
use App\Domain\Content\Models\Faq;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Models\Sector;
use App\Domain\Content\Models\Service;
use App\Domain\Platform\Models\User;
use Database\Factories\Content\ServiceFactory;

dataset('content models', [Service::class, Sector::class, CaseStudy::class, Article::class, Faq::class, Page::class]);

it('ouvre les contenus aux admins et éditeurs, pas aux commerciaux', function (string $model) {
    expect(User::factory()->admin()->create()->can('viewAny', $model))->toBeTrue()
        ->and(User::factory()->editor()->create()->can('viewAny', $model))->toBeTrue()
        ->and(User::factory()->editor()->create()->can('create', $model))->toBeTrue()
        ->and(User::factory()->sales()->create()->can('viewAny', $model))->toBeFalse();
})->with('content models');

it('réserve la publication et la suppression aux admins', function () {
    $service = ServiceFactory::new()->create();

    expect(User::factory()->admin()->create()->can('publish', $service))->toBeTrue()
        ->and(User::factory()->editor()->create()->can('publish', $service))->toBeFalse()
        ->and(User::factory()->editor()->create()->can('delete', $service))->toBeFalse()
        ->and(User::factory()->admin()->create()->can('delete', $service))->toBeTrue();
});

it('empêche un éditeur de modifier un contenu déjà publié', function () {
    $service = ServiceFactory::new()->create();
    $editor = User::factory()->editor()->create();
    expect($editor->can('update', $service))->toBeTrue();

    app(PublishContent::class)->handle($service, User::factory()->admin()->create());

    expect($editor->can('update', $service->fresh()))->toBeFalse()
        ->and(User::factory()->admin()->create()->can('update', $service->fresh()))->toBeTrue();
});

it("réserve l'attestation d'autorisation d'une réalisation aux admins", function () {
    expect(User::factory()->admin()->create()->can('attestAuthorization', new CaseStudy))->toBeTrue()
        ->and(User::factory()->editor()->create()->can('attestAuthorization', new CaseStudy))->toBeFalse();
});
