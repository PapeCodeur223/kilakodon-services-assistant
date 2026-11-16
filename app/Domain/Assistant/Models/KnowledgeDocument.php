<?php

namespace App\Domain\Assistant\Models;

use App\Domain\Assistant\Enums\KnowledgeSourceType;
use App\Domain\Assistant\Enums\KnowledgeStatus;
use App\Domain\Platform\Concerns\Auditable;
use App\Domain\Platform\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Document de la base de connaissances (TRD §7.1) : un contenu publié du site ou un document manuel.
 * Seuls les documents approuvés et non expirés sont interrogés par l'assistant.
 *
 * @property int $id
 * @property string $title
 * @property KnowledgeSourceType $source_type
 * @property int|null $source_id
 * @property string|null $body
 * @property string $url
 * @property KnowledgeStatus $status
 * @property int $version
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property Carbon|null $review_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, KnowledgeChunk> $chunks
 * @property-read User|null $approver
 */
class KnowledgeDocument extends Model
{
    use Auditable;

    protected $fillable = [
        'title', 'source_type', 'source_id', 'body', 'url', 'status', 'version',
        'approved_by', 'approved_at', 'review_at', 'expires_at',
    ];

    protected $attributes = [
        'status' => 'draft',
        'version' => 1,
    ];

    protected function casts(): array
    {
        return [
            'source_type' => KnowledgeSourceType::class,
            'status' => KnowledgeStatus::class,
            'version' => 'integer',
            'approved_at' => 'datetime',
            'review_at' => 'date',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSearchable(Builder $query): Builder
    {
        return $query->where('status', KnowledgeStatus::Approved)
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isManual(): bool
    {
        return $this->source_type === KnowledgeSourceType::Manual;
    }

    /**
     * @return HasMany<KnowledgeChunk, $this>
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class, 'document_id')->orderBy('position');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return list<string>
     */
    protected function auditExcluded(): array
    {
        return ['created_at', 'updated_at', 'body'];
    }
}
