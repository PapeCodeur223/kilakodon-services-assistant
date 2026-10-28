<?php

/**
 * Processus enfant du test de concurrence de ReferenceGenerator :
 * démarre l'application (base de test via l'environnement) et affiche une référence.
 * Usage : php tests/Support/next-reference.php <séquence> <année>
 * Code de sortie 1 et message sur la sortie d'erreur en cas d'échec.
 */

use App\Domain\Brief\Support\ReferenceGenerator;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';

try {
    $app = require __DIR__.'/../../bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();

    echo $app->make(ReferenceGenerator::class)->next((int) $argv[2], $argv[1]);
} catch (Throwable $e) {
    fwrite(STDERR, $e::class.' : '.$e->getMessage());
    exit(1);
}
