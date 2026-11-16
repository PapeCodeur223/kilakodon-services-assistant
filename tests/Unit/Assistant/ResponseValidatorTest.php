<?php

use App\Domain\Assistant\Conversation\FallbackTexts;
use App\Domain\Assistant\Conversation\ResponseValidator;
use App\Domain\Assistant\Enums\Grounding;
use App\Domain\Assistant\Enums\SuggestedAction;

function docs(): array
{
    return [
        ['id' => 11, 'document_id' => 1, 'title' => 'Stock et caisse', 'heading' => 'Stock et caisse — Fonctionnalités', 'content' => 'La caisse fonctionne hors-ligne et se synchronise.', 'url' => 'http://localhost/solutions/stock-caisse', 'score' => 3.0],
        ['id' => 12, 'document_id' => 2, 'title' => 'FAQ', 'heading' => 'FAQ', 'content' => 'Le paiement se fait par Orange Money ou virement.', 'url' => 'http://localhost/#faq', 'score' => 1.0],
    ];
}

function respond(array $overrides = []): array
{
    return [...[
        'answer' => 'Oui, la caisse fonctionne hors-ligne.',
        'grounding' => 'sourced',
        'citations' => [11],
        'suggested_action' => 'none',
        'clarifying_question' => null,
        'brief_draft' => null,
    ], ...$overrides];
}

it('accepte une réponse conforme et résout les citations', function () {
    $answer = (new ResponseValidator)->validate(respond(), docs());

    expect($answer->errorCode)->toBeNull()
        ->and($answer->grounding)->toBe(Grounding::Sourced)
        ->and($answer->citations)->toBe([['chunk_id' => 11, 'url' => 'http://localhost/solutions/stock-caisse', 'title' => 'Stock et caisse']]);
});

it('écarte les citations inventées et rétrograde « sourced » en « partial »', function () {
    $answer = (new ResponseValidator)->validate(respond(['citations' => [999]]), docs());

    expect($answer->citations)->toBe([])
        ->and($answer->grounding)->toBe(Grounding::Partial);
});

it('renvoie le repli « invalid_output » pour une sortie absente ou mal formée', function (?array $input) {
    $answer = (new ResponseValidator)->validate($input, docs());

    expect($answer->errorCode)->toBe('invalid_output')
        ->and($answer->answer)->toBe(FallbackTexts::INVALID_OUTPUT)
        ->and($answer->suggestedAction)->toBe(SuggestedAction::HandoffWhatsapp);
})->with([
    'absente' => [null],
    'réponse vide' => [respond(['answer' => '  '])],
    'ancrage inconnu' => [respond(['grounding' => 'certain'])],
    'action inconnue' => [respond(['suggested_action' => 'create_lead'])],
    'citations non numériques' => [respond(['citations' => ['abc']])],
    'trop de citations' => [respond(['citations' => [1, 2, 3, 4, 5]])],
    'réponse trop longue' => [respond(['answer' => str_repeat('a', 1501)])],
]);

it('bloque tout montant absent des sources et de la question (price_guard)', function (string $text) {
    $answer = (new ResponseValidator)->validate(respond(['answer' => $text, 'citations' => []]), docs(), 'Combien coûte un logiciel de caisse ?');

    expect($answer->errorCode)->toBe('price_guard')
        ->and($answer->answer)->toBe(FallbackTexts::PRICE)
        ->and($answer->suggestedAction)->toBe(SuggestedAction::StartDiagnostic);
})->with([
    'FCFA' => ['Comptez environ 500 000 FCFA.'],
    'F CFA' => ['Autour de 1 200 000 F CFA pour démarrer.'],
    'symbole euro' => ['Environ 800 € par mois.'],
    'euros en toutes lettres' => ['Environ 800 euros par mois.'],
    'dollar devant' => ['Comptez $500 de licence.'],
    'millions' => ['Cela tourne autour de 2 millions.'],
    'millions sans chiffre' => ['Il faut prévoir plusieurs millions.'],
    'abréviation k' => ['Un budget de 300k suffit.'],
]);

it('laisse passer un montant cité par le visiteur ou présent dans les sources', function () {
    $validator = new ResponseValidator;

    $fromQuestion = $validator->validate(respond(['answer' => 'Avec 500 000 FCFA, le diagnostic permettra de prioriser.', 'citations' => []]), docs(), 'J\'ai un budget de 500 000 FCFA');
    $sources = [...docs(), ['id' => 13, 'document_id' => 3, 'title' => 'Démo', 'heading' => 'Démo', 'content' => 'Frais de dossier : 10 000 FCFA.', 'url' => 'http://localhost/x', 'score' => 1.0]];
    $fromSources = $validator->validate(respond(['answer' => 'Les frais de dossier sont de 10 000 FCFA.', 'citations' => [13]]), $sources);

    expect($fromQuestion->errorCode)->toBeNull()
        ->and($fromSources->errorCode)->toBeNull();
});

it('ne prend pas un simple nombre pour un prix', function () {
    $answer = (new ResponseValidator)->validate(respond(['answer' => 'Le diagnostic prend 3 minutes et compte 12 questions.']), docs());

    expect($answer->errorCode)->toBeNull();
});

it('ne garde le brouillon de brief qu\'avec propose_brief et sans champ inventé', function () {
    $draft = ['sector' => 'Commerce', 'main_problem' => '  ', 'unexpected' => 'x', 'summary' => 'Boutique avec écarts de stock.'];
    $validator = new ResponseValidator;

    $with = $validator->validate(respond(['suggested_action' => 'propose_brief', 'grounding' => 'partial', 'citations' => [], 'brief_draft' => $draft]), docs());
    $without = $validator->validate(respond(['brief_draft' => $draft]), docs());

    expect($with->briefDraft)->toBe([
        'sector' => 'Commerce', 'company_size' => null, 'current_tools' => null, 'main_problem' => null,
        'desired_outcome' => null, 'timeline' => null, 'summary' => 'Boutique avec écarts de stock.',
    ])->and($without->briefDraft)->toBeNull();
});

it('bloque aussi un montant glissé dans la question de clarification', function () {
    $answer = (new ResponseValidator)->validate(respond(['citations' => [], 'clarifying_question' => 'Votre budget tourne-t-il autour de 2 000 000 FCFA ?']), docs());

    expect($answer->errorCode)->toBe('price_guard');
});

it('reconnaît la notation « F » seule et les montants en lettres', function (string $text) {
    expect((new ResponseValidator)->validate(respond(['answer' => $text, 'citations' => []]), docs())->errorCode)->toBe('price_guard');
})->with([
    'F seul' => ['Comptez 250 000 F pour démarrer.'],
    'en lettres' => ['Prévoyez cinq cent mille francs environ.'],
]);

it('vide les champs du brouillon de brief qui contiennent un montant inventé', function () {
    $draft = ['sector' => 'Commerce', 'desired_outcome' => 'ERP livré pour 1,5 million', 'summary' => 'Projet estimé à 900 000 FCFA.'];

    $answer = (new ResponseValidator)->validate(respond(['suggested_action' => 'propose_brief', 'grounding' => 'partial', 'citations' => [], 'brief_draft' => $draft]), docs());

    expect($answer->errorCode)->toBeNull()
        ->and($answer->briefDraft['sector'])->toBe('Commerce')
        ->and($answer->briefDraft['desired_outcome'])->toBeNull()
        ->and($answer->briefDraft['summary'])->toBe('');
});

it('refuse un délai de réponse inventé, mais accepte un délai publié', function () {
    $validator = new ResponseValidator;

    expect($validator->validate(respond(['answer' => 'Un conseiller vous répond sous 1 h.', 'citations' => []]), docs())->errorCode)->toBe('commitment_guard')
        ->and($validator->validate(respond(['answer' => 'Nous revenons vers vous dans l\'heure.', 'citations' => []]), docs())->errorCode)->toBe('commitment_guard');

    $sources = [...docs(), ['id' => 14, 'document_id' => 4, 'title' => 'FAQ', 'heading' => 'FAQ', 'content' => 'Nous répondons sous 24 h ouvrées.', 'url' => 'http://localhost/#faq', 'score' => 1.0]];
    expect($validator->validate(respond(['answer' => 'Nous répondons sous 24 h ouvrées.', 'citations' => [14]]), $sources)->errorCode)->toBeNull();
});
