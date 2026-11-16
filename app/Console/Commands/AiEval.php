<?php

namespace App\Console\Commands;

use App\Domain\Assistant\Evaluation\AiEvaluator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Yaml\Yaml;

/**
 * Corpus d'évaluation de l'assistant (AI_ASSISTANT §5) : `--driver=fake` en continu (mécanique du
 * pipeline, gardes, validateur) ; `--driver=anthropic` uniquement en manuel, avec confirmation, car
 * il consomme la vraie clé API. Rapport CSV avec une colonne vide pour le jugement humain.
 */
class AiEval extends Command
{
    protected $signature = 'kh:ai-eval
        {--driver=fake : fake ou anthropic}
        {--case=* : n\'exécuter que ces identifiants}
        {--file=tests/Ai/cases.yaml : fichier du corpus}
        {--strict : code de sortie en échec si un cas critique échoue}';

    protected $description = 'Joue le corpus d\'évaluation de l\'assistant et écrit un rapport CSV';

    public function handle(): int
    {
        if (! class_exists(Yaml::class)) {
            $this->error('symfony/yaml est absent (dépendance de développement) : lancez cette commande en local.');

            return self::FAILURE;
        }

        $driver = (string) $this->option('driver');
        if (! in_array($driver, ['fake', 'anthropic'], true)) {
            $this->error('Pilote inconnu : fake ou anthropic.');

            return self::FAILURE;
        }

        if ($driver === 'anthropic' && ! $this->confirm('Ce mode appelle l\'API Anthropic avec la clé du fichier .env et engage des frais. Continuer ?')) {
            return self::FAILURE;
        }

        config(['assistant.driver' => $driver]);
        app()->forgetInstance(AiEvaluator::class);

        $cases = Yaml::parseFile(base_path((string) $this->option('file')));
        $only = (array) $this->option('case');
        if ($only !== []) {
            $cases = array_values(array_filter($cases, fn (array $c): bool => in_array($c['id'], $only, true)));
        }

        $evaluator = app(AiEvaluator::class);
        $results = [];
        $bar = $this->output->createProgressBar(count($cases));
        foreach ($cases as $case) {
            $results[] = $evaluator->run($case);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        $failed = array_values(array_filter($results, fn (array $r): bool => ! $r['passed']));
        $criticalFailed = array_filter($failed, fn (array $r): bool => $r['critical']);

        $this->table(['Cas', 'Catégorie', 'Critique', 'Contrôles en échec'], array_map(fn (array $r): array => [
            $r['id'], $r['category'], $r['critical'] ? 'oui' : '',
            implode(', ', array_keys(array_filter($r['checks'], fn (bool $ok): bool => ! $ok))),
        ], $failed));

        $path = $this->report($results, $driver);
        $this->info(sprintf('%d cas, %d réussis, %d en échec (dont %d critiques). Rapport : %s',
            count($results), count($results) - count($failed), count($failed), count($criticalFailed), $path));

        if ($driver === 'fake') {
            $this->comment('Pilote factice : il vérifie la mécanique (gardes, validateur, actions), pas la qualité des réponses.');
        }

        return $this->option('strict') && $criticalFailed !== [] ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  list<array<string, mixed>>  $results
     */
    private function report(array $results, string $driver): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['id', 'categorie', 'critique', 'pilote', 'reponse', 'ancrage', 'action', 'citations', 'refus', 'controles', 'resultat', 'jugement humain (utile/fonde : 0-2)'], ';');

        foreach ($results as $r) {
            fputcsv($handle, [
                $r['id'], $r['category'], $r['critical'] ? 'oui' : 'non', $driver, $r['answer'], $r['grounding'],
                $r['suggested_action'], $r['citations'], $r['rejected'] ?? '',
                implode(' ', array_map(fn (string $k, bool $ok): string => $k.'='.($ok ? 'OK' : 'KO'), array_keys($r['checks']), $r['checks'])),
                $r['passed'] ? 'OK' : 'KO', '',
            ], ';');
        }

        rewind($handle);
        $path = 'ai-eval/eval-'.$driver.'-'.now(config('kh.display_timezone'))->format('Ymd-His').'.csv';
        Storage::disk('local')->put($path, "\u{FEFF}".stream_get_contents($handle));
        fclose($handle);

        return Storage::disk('local')->path($path);
    }
}
