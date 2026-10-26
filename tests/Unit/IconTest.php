<?php

use App\View\Components\Kh\Icon;

it('charge une icône Lucide du dépôt sans son commentaire de licence', function () {
    $inner = (new Icon('package'))->inner();

    expect($inner)->toContain('<path')
        ->not->toContain('<svg')
        ->not->toContain('@license');
});

it('refuse un nom d\'icône qui sortirait du dossier des SVG', function (string $name) {
    (new Icon($name))->inner();
})->with(['../../.env', 'brand:../x', 'inconnue'])->throws(InvalidArgumentException::class);
