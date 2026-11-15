<?php

namespace App\Domain\Assistant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fragment de 120 à 400 mots d'un document (index FULLTEXT sur `heading` et `content`).
 *
 * @property int $id
 * @property int $document_id
 * @property int $document_version
 * @property int $position
 * @property string $heading
 * @property string $content
 * @property string $url
 * @property int $words
 * @property-read KnowledgeDocument $document
 */
class KnowledgeChunk extends Model
{
    protected $fillable = ['document_id', 'document_version', 'position', 'heading', 'content', 'url', 'words'];

    protected function casts(): array
    {
        return [
            'document_version' => 'integer',
            'position' => 'integer',
            'words' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<KnowledgeDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'document_id');
    }
}
