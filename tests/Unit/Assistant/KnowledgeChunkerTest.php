<?php

use App\Domain\Assistant\Knowledge\KnowledgeChunker;

function words(int $count, string $word = 'stock'): string
{
    return implode(' ', array_fill(0, $count, $word));
}

it('coupe sur les titres et préfixe chaque fragment du titre du document', function () {
    $md = "## Caisse\n\n".words(150, 'caisse')."\n\n## Stock\n\n".words(150);
    $chunks = (new KnowledgeChunker)->chunk('Stocks & caisse', $md);

    expect($chunks)->toHaveCount(2)
        ->and($chunks[0]['heading'])->toBe('Stocks & caisse — Caisse')
        ->and($chunks[1]['heading'])->toBe('Stocks & caisse — Stock')
        ->and($chunks[0]['words'])->toBe(150);
});

it('regroupe les sections trop courtes et ne dépasse jamais 400 mots', function () {
    $md = "## A\n\n".words(30)."\n\n## B\n\n".words(30)."\n\n## C\n\n".words(30);
    $chunks = (new KnowledgeChunker)->chunk('Doc', $md);

    expect($chunks)->toHaveCount(1)
        ->and($chunks[0]['content'])->toContain('B : ')
        ->and($chunks[0]['words'])->toBeLessThanOrEqual(KnowledgeChunker::MAX_WORDS);
});

it('coupe une section trop longue entre deux paragraphes', function () {
    $md = "## Long\n\n".implode("\n\n", array_fill(0, 6, words(150)));
    $chunks = (new KnowledgeChunker)->chunk('Doc', $md);

    expect(count($chunks))->toBeGreaterThan(1);
    foreach ($chunks as $chunk) {
        expect($chunk['words'])->toBeLessThanOrEqual(KnowledgeChunker::MAX_WORDS);
    }
});

it('garde le texte sans titre et ignore un document vide', function () {
    expect((new KnowledgeChunker)->chunk('FAQ', 'Oui, la caisse fonctionne hors-ligne.'))->toHaveCount(1)
        ->and((new KnowledgeChunker)->chunk('Vide', "  \n "))->toBe([]);
});
