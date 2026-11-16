<?php

namespace App\Domain\Assistant\Actions;

use App\Domain\Assistant\Enums\KnowledgeSourceType;
use App\Domain\Assistant\Enums\KnowledgeStatus;
use App\Domain\Assistant\Knowledge\KnowledgeChunker;
use App\Domain\Assistant\Knowledge\KnowledgeSource;
use App\Domain\Assistant\Models\KnowledgeDocument;
use App\Domain\Content\Models\ContentModel;
use Illuminate\Support\Facades\DB;

/**
 * (Re)construit les fragments d'un document de la base de connaissances (TRD §7.1).
 * Les anciens fragments sont remplacés dans la même transaction. Un contenu qui n'est plus publié
 * retire son document. Un nouveau document reste en brouillon jusqu'à l'approbation d'un admin ;
 * un document déjà approuvé le reste quand son contenu publié évolue (nouvelle version).
 */
class RebuildKnowledge
{
    public function __construct(private readonly KnowledgeChunker $chunker) {}

    public function forContent(ContentModel $content): ?KnowledgeDocument
    {
        $type = KnowledgeSource::typeOf($content);
        if ($type === null) {
            return null;
        }

        $document = KnowledgeDocument::query()->where('source_type', $type)->where('source_id', $content->getKey())->first();

        if (! KnowledgeSource::eligible($content)) {
            if ($document !== null && $document->status !== KnowledgeStatus::Retired) {
                DB::transaction(function () use ($document): void {
                    $document->chunks()->delete();
                    $document->update(['status' => KnowledgeStatus::Retired]);
                });
            }

            return $document;
        }

        $source = KnowledgeSource::document($content);

        return DB::transaction(function () use ($document, $type, $content, $source): KnowledgeDocument {
            if ($document === null) {
                $document = KnowledgeDocument::query()->create([
                    'title' => mb_substr($source['title'], 0, 200),
                    'source_type' => $type,
                    'source_id' => $content->getKey(),
                    'url' => $source['url'],
                    'status' => KnowledgeStatus::Draft,
                ]);
            } else {
                $document->update([
                    'title' => mb_substr($source['title'], 0, 200),
                    'url' => $source['url'],
                    'version' => $document->version + 1,
                    // Un contenu republié redevient interrogeable s'il avait été retiré faute de publication.
                    'status' => $document->status === KnowledgeStatus::Retired && $document->approved_at !== null ? KnowledgeStatus::Approved : $document->status,
                ]);
            }

            $this->replaceChunks($document, $source['title'], $source['markdown']);

            return $document;
        });
    }

    /**
     * Document manuel : découpe son propre texte.
     */
    public function forManual(KnowledgeDocument $document): KnowledgeDocument
    {
        if ($document->source_type !== KnowledgeSourceType::Manual) {
            return $document;
        }

        return DB::transaction(function () use ($document): KnowledgeDocument {
            $this->replaceChunks($document, $document->title, (string) $document->body);

            return $document;
        });
    }

    private function replaceChunks(KnowledgeDocument $document, string $title, string $markdown): void
    {
        $document->chunks()->delete();

        foreach ($this->chunker->chunk($title, $markdown) as $position => $chunk) {
            $document->chunks()->create([
                'document_version' => $document->version,
                'position' => $position,
                'heading' => $chunk['heading'],
                'content' => $chunk['content'],
                'url' => $document->url,
                'words' => min($chunk['words'], 65535),
            ]);
        }
    }
}
