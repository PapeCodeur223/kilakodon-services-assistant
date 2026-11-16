<?php

use App\View\Support\AboutContent;

it('découpe le texte À propos en introduction, sections, éléments et engagement', function () {
    $about = AboutContent::parse(<<<'MD'
Intro de la page.

Notre conviction : partir du terrain.

## Notre histoire

Fondée en **2020** au Mali.

## Nos domaines d'expertise

Nous intervenons dans plusieurs domaines :

**Développement web** : sites et applications.

**Marketing digital** : visibilité en ligne.

## Notre mission

Rendre le numérique accessible.

**Notre engagement : vous accompagner.**
MD);

    expect($about['intro'])->toBe(['Intro de la page.', 'Notre conviction : partir du terrain.'])
        ->and($about['sections'])->toHaveCount(3)
        ->and(AboutContent::find($about['sections'], 'expertise')['items'])->toBe([
            ['title' => 'Développement web', 'text' => 'sites et applications.'],
            ['title' => 'Marketing digital', 'text' => 'visibilité en ligne.'],
        ])
        ->and(AboutContent::find($about['sections'], 'mission')['statement'])->toBe('Notre engagement : vous accompagner.')
        ->and(AboutContent::find($about['sections'], 'equipe'))->toBeNull();
});

it('choisit une icône par domaine et calcule les initiales', function () {
    expect(AboutContent::iconFor('Marketing digital'))->toBe('megaphone')
        ->and(AboutContent::iconFor('Solutions digitales et SaaS'))->toBe('cloud')
        ->and(AboutContent::iconFor('Autre chose'))->toBe('sparkles')
        ->and(AboutContent::initials('Pape Soumaila Traoré'))->toBe('PT');
});
