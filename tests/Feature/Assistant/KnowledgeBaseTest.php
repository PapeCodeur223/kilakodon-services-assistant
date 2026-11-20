<?php

use App\Domain\Assistant\Actions\ReviewKnowledgeDocument;
use App\Domain\Assistant\Enums\KnowledgeSourceType;
use App\Domain\Assistant\Enums\KnowledgeStatus;
use App\Domain\Assistant\Models\KnowledgeChunk;
use App\Domain\Assistant\Models\KnowledgeDocument;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\Faq;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Models\Service;
use App\Domain\Content\Support\PublishGuard;
use App\Domain\Platform\Models\User;
use App\Filament\Resources\KnowledgeDocuments\Pages\ListKnowledgeDocuments;
use Database\Seeders\ContentSeeder;
use Database\Seeders\KnowledgeSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;

function publishedFaq(string $question = 'La caisse fonctionne-t-elle sans Internet ?', string $answer = 'Oui, la caisse continue hors-ligne et se synchronise au retour du réseau.'): Faq
{
    $faq = new Faq(['question' => $question, 'answer' => $answer, 'category' => 'general']);
    // Statut et date de publication ne sont pas assignables en masse : on passe par forceFill sous PublishGuard.
    PublishGuard::allow(fn () => $faq->forceFill(['status' => ContentStatus::Published, 'published_at' => now()])->save());

    return $faq;
}

it('crée un document en brouillon et ses fragments quand un contenu est publié', function () {
    $faq = publishedFaq();

    $document = KnowledgeDocument::query()->where('source_type', KnowledgeSourceType::Faq)->where('source_id', $faq->id)->sole();
    expect($document->status)->toBe(KnowledgeStatus::Draft)
        ->and($document->title)->toBe($faq->question)
        ->and($document->chunks()->count())->toBe(1)
        ->and($document->chunks()->first()->content)->toContain('hors-ligne');
});

it('remplace les fragments à chaque modification et incrémente la version', function () {
    $faq = publishedFaq();
    PublishGuard::allow(fn () => $faq->forceFill(['answer' => 'Nouvelle réponse sur la synchronisation.'])->save());

    $document = KnowledgeDocument::query()->where('source_id', $faq->id)->where('source_type', KnowledgeSourceType::Faq)->sole();
    expect($document->version)->toBe(2)
        ->and(KnowledgeChunk::query()->where('document_id', $document->id)->count())->toBe(1)
        ->and($document->chunks()->first()->content)->toContain('Nouvelle réponse')
        ->and($document->chunks()->first()->document_version)->toBe(2);
});

it('retire le document quand le contenu n\'est plus publié', function () {
    $faq = publishedFaq();
    PublishGuard::allow(fn () => $faq->forceFill(['status' => ContentStatus::Archived])->save());

    $document = KnowledgeDocument::query()->where('source_id', $faq->id)->where('source_type', KnowledgeSourceType::Faq)->sole();
    expect($document->status)->toBe(KnowledgeStatus::Retired)
        ->and($document->chunks()->count())->toBe(0);
});

it('exclut les pages légales de la base de connaissances', function () {
    $page = new Page(['slug' => 'mentions-legales', 'title' => 'Mentions légales', 'body' => "## Éditeur\n\nTexte.", 'template' => 'legal']);
    PublishGuard::allow(fn () => $page->forceFill(['status' => ContentStatus::Published, 'published_at' => now()])->save());

    expect(KnowledgeDocument::query()->where('source_type', KnowledgeSourceType::Page)->count())->toBe(0);
});

it('ne laisse qu\'un admin approuver ou retirer un document', function () {
    $document = KnowledgeDocument::query()->where('source_id', publishedFaq()->id)->sole();

    expect(fn () => app(ReviewKnowledgeDocument::class)->approve($document, User::factory()->editor()->create()))
        ->toThrow(AuthorizationException::class);

    $admin = User::factory()->admin()->create();
    app(ReviewKnowledgeDocument::class)->approve($document, $admin);

    expect($document->refresh()->status)->toBe(KnowledgeStatus::Approved)
        ->and($document->approved_by)->toBe($admin->id)
        ->and(KnowledgeDocument::query()->searchable()->count())->toBe(1);
});

it('découpe une fiche solution complète (problème, bénéfices, FAQ) sans champ interne', function () {
    $this->seed(ContentSeeder::class);
    $service = Service::query()->published()->first();

    if ($service === null) {
        // Fiches en brouillon dans le seed (elles contiennent des points à valider) : on en publie une pour le test.
        $service = Service::query()->firstOrFail();
        PublishGuard::allow(fn () => $service->forceFill(['status' => ContentStatus::Published, 'published_at' => now(), 'duration_hint' => '3 à 6 semaines'])->save());
    }

    $chunks = KnowledgeDocument::query()->where('source_type', KnowledgeSourceType::Service)->where('source_id', $service->id)->sole()->chunks;
    $text = $chunks->pluck('content')->implode(' ');

    expect($chunks)->not->toBeEmpty()
        ->and($text)->toContain($service->problem)
        ->and($text)->not->toContain('MOD_');
});

it('approuve une sélection de documents d’un coup, pour un admin seulement (lot 7)', function () {
    $first = publishedFaq('Question 1 ?', 'Réponse publique numéro un.');
    $second = publishedFaq('Question 2 ?', 'Réponse publique numéro deux.');
    $documents = KnowledgeDocument::query()->whereIn('source_id', [$first->id, $second->id])->where('source_type', KnowledgeSourceType::Faq)->get();

    $this->actingAs(User::factory()->admin()->withMfa()->create());
    Livewire::test(ListKnowledgeDocuments::class)
        ->callTableBulkAction('approveSelected', $documents);

    expect(KnowledgeDocument::query()->whereKey($documents->modelKeys())->where('status', KnowledgeStatus::Approved)->count())->toBe(2);

    $this->actingAs(User::factory()->editor()->create());
    Livewire::test(ListKnowledgeDocuments::class)
        ->assertTableBulkActionHidden('approveSelected');
});

it('le seeder est idempotent', function () {
    publishedFaq();
    $this->seed(KnowledgeSeeder::class);
    $this->seed(KnowledgeSeeder::class);

    expect(KnowledgeDocument::query()->count())->toBe(1)
        ->and(KnowledgeDocument::query()->sole()->version)->toBe(1);
});
