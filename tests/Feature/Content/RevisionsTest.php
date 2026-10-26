<?php

use App\Domain\Content\Actions\PublishContent;
use App\Domain\Content\Actions\RestoreRevision;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Platform\Models\User;
use Database\Factories\Content\PageFactory;
use Illuminate\Auth\Access\AuthorizationException;

it('crée une révision à la création puis à chaque modification éditoriale', function () {
    $page = PageFactory::new()->create(['title' => 'Version 1']);
    $page->update(['title' => 'Version 2']);
    $page->update(['title' => 'Version 2']); // aucune modification : pas de révision

    expect($page->revisions()->pluck('version')->all())->toBe([2, 1])
        ->and($page->revisions()->first()->data['title'])->toBe('Version 2');
});

it('restaure une révision précédente en créant une nouvelle version', function () {
    $editor = User::factory()->editor()->create();
    $page = PageFactory::new()->create(['title' => 'Titre initial', 'body' => 'Texte initial']);
    $page->update(['title' => 'Titre modifié', 'body' => 'Texte modifié']);
    $first = $page->revisions()->where('version', 1)->firstOrFail();

    app(RestoreRevision::class)->handle($page, $first, $editor);

    $page->refresh();
    expect($page->title)->toBe('Titre initial')
        ->and($page->body)->toBe('Texte initial')
        ->and($page->revisions()->max('version'))->toBe(3)
        ->and($page->status)->toBe(ContentStatus::Draft);
});

it("n'autorise pas un éditeur à restaurer une version d'un contenu publié", function () {
    $page = PageFactory::new()->create(['title' => 'A']);
    $page->update(['title' => 'B']);
    app(PublishContent::class)->handle($page, User::factory()->admin()->create());

    expect(fn () => app(RestoreRevision::class)->handle($page, $page->revisions()->where('version', 1)->firstOrFail(), User::factory()->editor()->create()))
        ->toThrow(AuthorizationException::class);
});

it('refuse de restaurer la révision d’un autre contenu', function () {
    $admin = User::factory()->admin()->create();
    $a = PageFactory::new()->create();
    $b = PageFactory::new()->create();

    expect(fn () => app(RestoreRevision::class)->handle($a, $b->revisions()->firstOrFail(), $admin))
        ->toThrow(InvalidArgumentException::class);
});
