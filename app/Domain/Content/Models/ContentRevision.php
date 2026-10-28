<?php

namespace App\Domain\Content\Models;

use App\Domain\Platform\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $version
 * @property array<string, mixed> $data
 * @property int|null $user_id
 * @property Carbon|null $created_at
 * @property-read User|null $user
 */
class ContentRevision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['version', 'data', 'user_id'];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'data' => 'array',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function revisionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
