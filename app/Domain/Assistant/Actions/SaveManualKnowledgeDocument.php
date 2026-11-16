<?php

namespace App\Domain\Assistant\Actions;

use App\Domain\Assistant\Enums\KnowledgeSourceType;
use App\Domain\Assistant\Enums\KnowledgeStatus;
use App\Domain\Assistant\Models\KnowledgeDocument;
use Illuminate\Support\Facades\DB;

/**
 * Document manuel de la base de connaissances (admin ou éditeur). Toute création ou modification le
 * remet en brouillon (nouvelle version) : il n'est interrogeable qu'après approbation par un admin.
 */
class SaveManualKnowledgeDocument
{
    public function __construct(private readonly RebuildKnowledge $rebuild) {}

    /**
     * @param  array{title: string, body: string, url?: string|null, review_at?: mixed, expires_at?: mixed}  $data
     */
    public function handle(array $data, ?KnowledgeDocument $document = null): KnowledgeDocument
    {
        return DB::transaction(function () use ($data, $document): KnowledgeDocument {
            $document ??= new KnowledgeDocument(['source_type' => KnowledgeSourceType::Manual, 'version' => 0]);

            $document->fill([
                'title' => trim($data['title']),
                'body' => trim($data['body']),
                // Lien interne uniquement : une URL externe n'est jamais citée comme source.
                'url' => filled($data['url'] ?? null) && str_starts_with(trim((string) $data['url']), rtrim(url('/'), '/'))
                    ? trim((string) $data['url'])
                    : route('home'),
                'review_at' => $data['review_at'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'status' => KnowledgeStatus::Draft,
                'version' => $document->version + 1,
                'approved_by' => null,
                'approved_at' => null,
            ])->save();

            return $this->rebuild->forManual($document);
        });
    }
}
