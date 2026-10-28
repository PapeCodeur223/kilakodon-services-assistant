<?php

use App\Domain\Brief\Support\ReferenceGenerator;
use App\Domain\Platform\Models\Sequence;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

it('génère des références KLK-AAAA-NNNN séquentielles par année', function () {
    $generator = app(ReferenceGenerator::class);

    expect($generator->next(2026))->toBe('KLK-2026-0001')
        ->and($generator->next(2026))->toBe('KLK-2026-0002')
        ->and($generator->next(2027))->toBe('KLK-2027-0001')
        ->and($generator->next())->toMatch('/^KLK-\d{4}-\d{4}$/')
        ->and(ReferenceGenerator::format(2026, 42))->toBe('KLK-2026-0042');
});

it('ne produit aucune collision avec 20 générations parallèles (BRF-03)', function () {
    // Vrais processus concurrents sur la base de test : ils écrivent hors de la transaction du test,
    // sur une séquence dédiée, nettoyée à la fin.
    $sequence = 'concurrency_'.bin2hex(random_bytes(4));
    $php = (new PhpExecutableFinder)->find() ?: PHP_BINARY;
    $env = [
        'APP_ENV' => 'testing',
        'DB_DATABASE' => config('database.connections.mysql.database'),
        'DB_PREFIX' => config('database.connections.mysql.prefix'),
        'CACHE_STORE' => 'array',
    ];

    // Compteur créé à l'avance, comme le fait la tâche planifiée quotidienne.
    config(['database.connections.cleanup' => config('database.connections.mysql')]);
    DB::connection('cleanup')->table('sequences')->insert(['name' => $sequence, 'year' => 2099, 'last_value' => 0]);

    $processes = [];
    for ($i = 0; $i < 20; $i++) {
        $process = new Process([$php, base_path('tests/Support/next-reference.php'), $sequence, '2099'], base_path(), $env, null, 120);
        $process->start();
        $processes[] = $process;
    }

    $references = [];
    foreach ($processes as $process) {
        $process->wait();
        expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());
        $references[] = trim($process->getOutput());
    }

    // Nettoyage par une connexion distincte (la ligne a été validée par les processus enfants).
    DB::connection('cleanup')->table('sequences')->where('name', $sequence)->delete();
    DB::purge('cleanup');

    sort($references);
    expect(array_unique($references))->toHaveCount(20)
        ->and($references)->toBe(array_map(fn (int $n): string => ReferenceGenerator::format(2099, $n), range(1, 20)));
});

it('prépare les compteurs de l\'année en cours et de la suivante', function () {
    app(ReferenceGenerator::class)->prepare();
    $year = (int) now(config('kh.display_timezone'))->year;

    expect(Sequence::query()->where('name', 'brief')->orderBy('year')->pluck('year')->all())->toBe([$year, $year + 1]);
});
