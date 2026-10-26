<?php

use App\View\Support\Accent;

it('convertit le premier fragment *…* en <em>', function () {
    expect((string) Accent::html('Pilotez avec des outils *faits pour elle*.'))
        ->toBe('Pilotez avec des outils <em>faits pour elle</em>.');
});

it('échappe le HTML avant de produire <em>', function () {
    expect((string) Accent::html('<script>alert(1)</script> *ok*'))
        ->toBe('&lt;script&gt;alert(1)&lt;/script&gt; <em>ok</em>');
});

it('ne met en accent qu\'un seul fragment par titre', function () {
    expect((string) Accent::html('*un* et *deux*'))->toBe('<em>un</em> et *deux*');
});

it('fournit une version texte sans astérisques', function () {
    expect(Accent::plain('Six expertises, *un seul interlocuteur*'))->toBe('Six expertises, un seul interlocuteur');
});
