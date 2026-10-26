<?php

use App\Domain\Content\Actions\ChangeContentStatus;
use App\Domain\Content\Actions\PublishContent;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Exceptions\ContentTransitionException;
use App\Domain\Content\Models\Service;
use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\AuditLog;
use App\Domain\Platform\Models\User;
use Database\Factories\Content\CaseStudyFactory;
use Database\Factories\Content\ServiceFactory;
use Illuminate\Auth\Access\AuthorizationException;

it('publie un contenu quand un admin le demande et journalise la publication', function () {
    $admin = User::factory()->admin()->create();
    $service = ServiceFactory::new()->create();

    app(PublishContent::class)->handle($service, $admin);

    $service->refresh();
    expect($service->status)->toBe(ContentStatus::Published)
        ->and($service->published_at)->not->toBeNull()
        ->and($service->isPublished())->toBeTrue()
        ->and(AuditLog::query()->where('action', AuditAction::Published)->where('auditable_id', $service->id)->exists())->toBeTrue();
});

it('refuse la publication à un éditeur et à un commercial', function (string $role) {
    $service = ServiceFactory::new()->create();

    expect(fn () => app(PublishContent::class)->handle($service, User::factory()->{$role}()->create()))
        ->toThrow(AuthorizationException::class);

    expect($service->fresh()->status)->toBe(ContentStatus::Draft);
})->with(['editor', 'sales']);

it('refuse de publier un contenu qui contient encore [À VALIDER]', function () {
    $service = ServiceFactory::new()->create(['duration_hint' => '3 à 6 semaines [À VALIDER]']);

    expect(fn () => app(PublishContent::class)->handle($service, User::factory()->admin()->create()))
        ->toThrow(ContentTransitionException::class, 'duration_hint');
});

it('interdit de publier en contournant l’action', function () {
    $service = ServiceFactory::new()->create();
    $service->status = ContentStatus::Published;

    expect(fn () => $service->save())->toThrow(LogicException::class);
});

it('bloque la publication d’une réalisation sans autorisation client accordée', function () {
    $caseStudy = CaseStudyFactory::new()->create();

    expect(fn () => app(PublishContent::class)->handle($caseStudy, User::factory()->admin()->create()))
        ->toThrow(ContentTransitionException::class);

    expect($caseStudy->fresh()->status)->toBe(ContentStatus::Draft);
});

it('publie une réalisation dont l’autorisation est accordée', function () {
    $caseStudy = CaseStudyFactory::new()->authorized()->create();

    app(PublishContent::class)->handle($caseStudy, User::factory()->admin()->create());

    expect($caseStudy->fresh()->status)->toBe(ContentStatus::Published);
});

it('laisse un éditeur soumettre un brouillon à la relecture', function () {
    $service = ServiceFactory::new()->create();

    app(ChangeContentStatus::class)->handle($service, ContentStatus::Review, User::factory()->editor()->create());

    expect($service->fresh()->status)->toBe(ContentStatus::Review);
});

it('réserve la dépublication et l’archivage aux admins', function () {
    $service = ServiceFactory::new()->create();
    app(PublishContent::class)->handle($service, User::factory()->admin()->create());

    expect(fn () => app(ChangeContentStatus::class)->handle($service, ContentStatus::Draft, User::factory()->editor()->create()))
        ->toThrow(AuthorizationException::class);

    app(ChangeContentStatus::class)->handle($service, ContentStatus::Archived, User::factory()->admin()->create());
    expect($service->fresh()->status)->toBe(ContentStatus::Archived);
});

it('refuse les transitions non prévues et le passage à « publié » hors PublishContent', function () {
    $service = ServiceFactory::new()->create();
    $admin = User::factory()->admin()->create();

    expect(fn () => app(ChangeContentStatus::class)->handle($service, ContentStatus::Published, $admin))
        ->toThrow(ContentTransitionException::class);

    $service->forceFill(['status' => ContentStatus::Archived])->save();
    expect(fn () => app(ChangeContentStatus::class)->handle($service, ContentStatus::Review, $admin))
        ->toThrow(ContentTransitionException::class);
});

it('ne montre au public que les contenus publiés', function () {
    $draft = ServiceFactory::new()->create();
    $published = ServiceFactory::new()->create();
    app(PublishContent::class)->handle($published, User::factory()->admin()->create());

    expect(Service::query()->published()->pluck('id')->all())->toBe([$published->id])
        ->and(Service::query()->visible(preview: true)->count())->toBe(2)
        ->and($draft->isPublished())->toBeFalse();
});
