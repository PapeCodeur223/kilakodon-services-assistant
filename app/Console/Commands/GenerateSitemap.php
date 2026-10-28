<?php

namespace App\Console\Commands;

use App\Domain\Content\Services\SitemapBuilder;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Régénère le sitemap.xml (contenus publiés uniquement)';

    public function handle(SitemapBuilder $sitemap): int
    {
        $count = substr_count($sitemap->refresh(), '<url>');
        $this->components->info("Sitemap régénéré : {$count} URL.");

        return self::SUCCESS;
    }
}
