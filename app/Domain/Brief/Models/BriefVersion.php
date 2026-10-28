<?php

namespace App\Domain\Brief\Models;

use App\Domain\Brief\Enums\SummarySource;
use App\Domain\Brief\Enums\VersionAuthor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Version figée d'un brief (BRF-02) : jamais modifiée ni supprimée après création.
 *
 * `declared` : ce que le prospect a dit ; `recommended` : copie figée des règles ;
 * `summary` : résumé (IA ou gabarit), éventuellement corrigé.
 *
 * @property int $id
 * @property int $brief_id
 * @property int $version
 * @property array<string, mixed> $declared
 * @property array<string, mixed> $recommended
 * @property string $summary
 * @property SummarySource $summary_source
 * @property string|null $prospect_comment
 * @property VersionAuthor $created_by
 * @property Carbon|null $created_at
 * @property-read Brief $brief
 */
class BriefVersion extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'brief_id', 'version', 'declared', 'recommended', 'summary', 'summary_source', 'prospect_comment', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'declared' => 'array',
            'recommended' => 'array',
            'summary_source' => SummarySource::class,
            'created_by' => VersionAuthor::class,
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('Une version de brief est immuable.'));
        static::deleting(fn (): never => throw new LogicException('Une version de brief est immuable.'));
    }

    /**
     * @return BelongsTo<Brief, $this>
     */
    public function brief(): BelongsTo
    {
        return $this->belongsTo(Brief::class);
    }
}
