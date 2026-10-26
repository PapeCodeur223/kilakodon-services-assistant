<?php

use App\View\Support\Halftone;

it('produit une sphère déterministe inscrite dans la grille 11 × 11', function () {
    $points = Halftone::points(240);

    expect($points)->not->toBeEmpty()
        ->and(count($points))->toBeLessThanOrEqual(121)
        ->and(Halftone::points(240))->toBe($points);
});

it('omet les points trop petits et fait décroître le rayon de gauche à droite', function () {
    $points = collect(Halftone::points(240));

    expect($points->min('r'))->toBeGreaterThanOrEqual(Halftone::MIN_RADIUS);

    $middleRow = $points->filter(fn ($p) => abs($p['cy'] - 120) < 1)->sortBy('cx')->values();
    expect($middleRow->first()['r'])->toBeGreaterThan($middleRow->last()['r']);
});
