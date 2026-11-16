<?php

use App\Domain\Assistant\Enums\KnowledgeSourceType;
use App\Domain\Assistant\Enums\KnowledgeStatus;
use App\Domain\Assistant\Knowledge\Retriever;
use App\Domain\Assistant\Models\KnowledgeDocument;

/**
 * Jeu de fragments fixe (TRD §13). Dans les tests, les données ne sont pas validées en base :
 * l'index FULLTEXT ne les voit pas et la recherche passe par son repli par termes, testé ici.
 */
function knowledge(string $title, string $content, KnowledgeStatus $status = KnowledgeStatus::Approved, ?string $expiresAt = null): KnowledgeDocument
{
    $document = KnowledgeDocument::query()->create([
        'title' => $title, 'source_type' => KnowledgeSourceType::Manual, 'url' => 'https://kilakodon.test/'.str($title)->slug(),
        'status' => $status, 'expires_at' => $expiresAt,
    ]);
    $document->chunks()->create(['document_version' => 1, 'position' => 0, 'heading' => $title, 'content' => $content, 'url' => $document->url, 'words' => str_word_count($content)]);

    return $document;
}

beforeEach(function () {
    knowledge('Stocks, facturation & caisse', 'Une caisse rapide qui fonctionne hors-ligne, la gestion du stock en temps réel et des alertes de rupture.');
    knowledge('Applications mobiles', 'Des applications pour vos équipes terrain : commerciaux, livreurs et agents, même avec un réseau faible.');
    knowledge('Méthode', 'Écoute et cahier des charges, conception, développement par modules, tests, mise en service et formation.');
    knowledge('Brouillon secret', 'Caisse caisse caisse : document non approuvé.', KnowledgeStatus::Draft);
    knowledge('Document expiré', 'Caisse et stock : ancienne offre.', KnowledgeStatus::Approved, now()->subDay()->toDateTimeString());
});

it('normalise la question : minuscules, sans accents ni mots vides', function () {
    expect(app(Retriever::class)->terms('Bonjour, vous faites des LOGICIELS de caisse à Bamako ?'))
        ->toBe(['logiciels', 'caisse', 'bamako']);
});

it('trouve le fragment le plus pertinent en premier', function () {
    $results = app(Retriever::class)->search('Vous faites des logiciels de caisse ?');

    expect($results)->not->toBeEmpty()
        ->and($results[0]['title'])->toBe('Stocks, facturation & caisse')
        ->and($results[0])->toHaveKeys(['id', 'heading', 'content', 'url', 'score']);
});

it('ignore les documents en brouillon ou expirés', function () {
    $titles = array_column(app(Retriever::class)->search('caisse stock'), 'title');

    expect($titles)->not->toContain('Brouillon secret')->not->toContain('Document expiré');
});

it('élargit la recherche avec les synonymes métier', function () {
    // « appli » n'apparaît dans aucun fragment ; ses synonymes (« application », « mobile ») oui.
    expect(array_column(app(Retriever::class)->search('Je veux une appli'), 'title'))->toContain('Applications mobiles');
});

it('ne renvoie rien pour une question vide ou sans terme utile', function () {
    expect(app(Retriever::class)->search('   '))->toBe([])
        ->and(app(Retriever::class)->search('est-ce que vous ?'))->toBe([]);
});

it('limite le nombre de fragments et la taille du contexte', function () {
    foreach (range(1, 10) as $i) {
        knowledge("Fiche stock {$i}", str_repeat('stock inventaire ', 200));
    }
    config(['assistant.retrieval.max_context_words' => 900]);

    $results = app(Retriever::class)->search('stock');

    expect(count($results))->toBeLessThanOrEqual(6)
        ->and(array_sum(array_map(fn ($r) => str_word_count($r['content']), $results)))->toBeLessThanOrEqual(900);
});
