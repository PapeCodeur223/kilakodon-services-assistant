<?php

use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Support\PublishGuard;
use App\View\Support\MethodContent;
use Database\Factories\Content\PageFactory;

const METHOD_BODY = <<<'MD'
Chaque projet suit les mêmes étapes.

## 1. Écoute & cahier des charges

Nous partons de votre terrain.

**Livrable :** cahier des charges et devis détaillé.

## 2. Conception

Nous dessinons les écrans.

**Livrable :** maquettes validées.

## Nos engagements

- Un périmètre écrit
- Votre validation à chaque étape
MD;

function publishedMethodPage(string $body = METHOD_BODY): void
{
    PublishGuard::allow(fn () => PageFactory::new()->create([
        'slug' => 'methode',
        'title' => 'Une méthode claire, *du premier échange au support*',
        'body' => $body,
        'status' => ContentStatus::Published,
        'published_at' => now(),
    ]));
}

it('lit les étapes, les livrables et les engagements du texte de la page', function () {
    $method = MethodContent::parse(METHOD_BODY);

    expect($method['intro'])->toBe('Chaque projet suit les mêmes étapes.')
        ->and($method['steps'])->toHaveCount(2)
        ->and($method['steps'][0])->toBe(['number' => 1, 'title' => 'Écoute & cahier des charges', 'text' => 'Nous partons de votre terrain.', 'deliverable' => 'cahier des charges et devis détaillé.'])
        ->and($method['steps'][1]['deliverable'])->toBe('maquettes validées.')
        ->and($method['engagements'])->toBe(['Un périmètre écrit', 'Votre validation à chaque étape']);
});

it('présente la méthode en frise, avec ancres, livrables et engagements', function () {
    publishedMethodPage();

    $this->get('/methode')
        ->assertOk()
        ->assertSee('Un livrable', false)
        ->assertSee('href="#etape-1"', false)
        ->assertSee('id="etape-2"', false)
        ->assertSee('Étape 2 sur 2')
        ->assertSee('maquettes validées.')
        ->assertSee('Un périmètre écrit')
        ->assertSee('entry=methode', false);
});

it('revient au gabarit simple si le texte ne suit plus la forme des étapes', function () {
    publishedMethodPage("## Notre approche\n\nUn texte libre sans étapes numérotées.");

    $this->get('/methode')
        ->assertOk()
        ->assertSee('Un texte libre sans étapes numérotées.')
        ->assertDontSee('href="#etape-1"', false);
});
