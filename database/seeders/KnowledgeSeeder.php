<?php

namespace Database\Seeders;

use App\Domain\Assistant\Actions\RebuildKnowledge;
use App\Domain\Assistant\Knowledge\KnowledgeSource;
use App\Domain\Assistant\Models\KnowledgeDocument;
use App\Domain\Content\Models\ContentModel;
use Illuminate\Database\Seeder;

/**
 * Base de connaissances depuis les contenus publiés (BACKEND_SCHEMA §9) : documents créés en brouillon,
 * à approuver par un admin. Idempotent : un document existant n'est pas recréé.
 */
class KnowledgeSeeder extends Seeder
{
    public function run(RebuildKnowledge $rebuild): void
    {
        foreach (array_keys(KnowledgeSource::TYPES) as $model) {
            /** @var iterable<ContentModel> $contents */
            $contents = $model::query()->published()->get();

            foreach ($contents as $content) {
                $exists = KnowledgeDocument::query()
                    ->where('source_type', KnowledgeSource::typeOf($content))
                    ->where('source_id', $content->getKey())
                    ->exists();

                if (! $exists) {
                    $rebuild->forContent($content);
                }
            }
        }
    }
}
