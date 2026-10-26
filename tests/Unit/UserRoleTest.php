<?php

use App\Domain\Platform\Enums\UserRole;

it('expose les trois rôles avec leurs libellés français', function () {
    expect(UserRole::options())->toBe([
        'admin' => 'Administrateur',
        'sales' => 'Commercial',
        'editor' => 'Éditeur',
    ]);
});
