<?php

namespace App\Console\Commands;

use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\CaseStudy;
use App\Domain\Content\Models\Faq;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Models\Sector;
use App\Domain\Content\Models\Service;
use App\Domain\Content\Services\HomeContent;
use App\Domain\Content\Support\PendingValidation;
use App\Domain\Platform\Services\Settings;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

/**
 * Liste les « [À VALIDER] » restants (critère Go/No-Go, PRD §8).
 * Code de sortie 1 tant qu'il en reste dans les contenus publiés, les paramètres ou l'accueil.
 */
class ContentAudit extends Command
{
    protected $signature = 'kh:content-audit {--all : Inclure aussi les brouillons}';

    protected $description = 'Liste les informations « [À VALIDER] » restantes (contenus publiés, paramètres, accueil)';

    private const MODELS = [
        Service::class => 'Solution',
        Sector::class => 'Secteur',
        CaseStudy::class => 'Réalisation',
        Article::class => 'Article',
        Faq::class => 'FAQ',
        Page::class => 'Page',
    ];

    public function handle(Settings $settings): int
    {
        $rows = [];

        foreach (self::MODELS as $model => $label) {
            $query = $model::query();
            if (! $this->option('all')) {
                $query->published();
            }

            foreach ($query->get() as $content) {
                /** @var Service|Sector|CaseStudy|Article|Faq|Page $content */
                foreach ($content->pendingValidationFields() as $field) {
                    $rows[] = [$label, $this->name($content), $content->status->label(), $field];
                }
            }
        }

        foreach ($settings->all() as $key => $value) {
            foreach (PendingValidation::find($value) as $path) {
                $rows[] = ['Paramètre', $key, '—', $path];
            }
        }

        foreach (PendingValidation::find(HomeContent::raw()) as $path) {
            $rows[] = ['Accueil', 'resources/content/home.php', 'masqué au public', $path];
        }

        if ($rows === []) {
            $this->components->info('Aucun « [À VALIDER] » restant.');

            return self::SUCCESS;
        }

        $this->table(['Type', 'Élément', 'Statut', 'Champ'], $rows);
        $this->components->warn(count($rows).' information(s) à valider par Kilakodon avant la mise en production.');

        return self::FAILURE;
    }

    private function name(Model $content): string
    {
        $attributes = $content->getAttributes();

        return (string) ($attributes['slug'] ?? $attributes['question'] ?? $content->getKey());
    }
}
