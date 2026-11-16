<?php

namespace App\Domain\Assistant\Jobs;

use App\Domain\Assistant\Actions\RebuildKnowledge;
use App\Domain\Assistant\Knowledge\KnowledgeSource;
use App\Domain\Content\Models\ContentModel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Reconstruit la base de connaissances d'un contenu après sa publication ou sa modification (TRD §10).
 * Idempotent : rejouer le job produit les mêmes fragments.
 */
class RebuildKnowledgeForSource implements ShouldQueue
{
    use Queueable;

    /**
     * @param  class-string<ContentModel>  $contentClass
     */
    public function __construct(
        public readonly string $contentClass,
        public readonly int $contentId,
    ) {}

    public function handle(RebuildKnowledge $rebuild): void
    {
        if (! array_key_exists($this->contentClass, KnowledgeSource::TYPES)) {
            return;
        }

        $content = $this->contentClass::query()->find($this->contentId);

        if ($content instanceof ContentModel) {
            $rebuild->forContent($content);
        }
    }
}
