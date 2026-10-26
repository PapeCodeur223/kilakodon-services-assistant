<?php

use App\Domain\Platform\Support\WhatsAppLink;

it('construit un lien wa.me encodé avec le message général', function () {
    expect(WhatsAppLink::for())
        ->toBe('https://wa.me/22374666489?text='.rawurlencode('Bonjour Kilakodon, je souhaite échanger sur un projet.'));
});

it('inclut la référence du brief dans le message', function () {
    $url = WhatsAppLink::forBrief('KLK-2026-0042');

    expect(rawurldecode(parse_url($url, PHP_URL_QUERY)))->toContain('mon brief KLK-2026-0042');
});

it('encode correctement les caractères spéciaux', function () {
    $url = WhatsAppLink::forSolution('Stocks, facturation & caisse');

    expect($url)->not->toContain(' ')
        ->not->toContain('&caisse')
        ->and(rawurldecode(explode('text=', $url)[1]))->toBe('Bonjour Kilakodon, je m\'intéresse à « Stocks, facturation & caisse ».');
});

it('limite le résumé de l’assistant à 300 caractères', function () {
    $url = WhatsAppLink::forAssistant(str_repeat('besoin ', 100));
    $summary = explode('Mon besoin : ', rawurldecode(explode('text=', $url)[1]))[1];

    expect(mb_strlen($summary))->toBeLessThanOrEqual(WhatsAppLink::ASSISTANT_SUMMARY_MAX);
});

it('affiche les numéros maliens par paires', function () {
    expect(WhatsAppLink::display('+22374666489'))->toBe('+223 74 66 64 89');
});
