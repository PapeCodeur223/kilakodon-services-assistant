<?php

namespace App\Domain\Platform\Models;

use App\Domain\Platform\Enums\AuditAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Journal en écriture seule : aucune modification ni suppression.
 *
 * @property int $id
 * @property int|null $user_id
 * @property AuditAction $action
 * @property string|null $auditable_type
 * @property int|null $auditable_id
 * @property array<string, mixed>|null $changes
 * @property string|null $ip_hash
 * @property Carbon|null $created_at
 * @property-read User|null $user
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['action', 'changes', 'ip_hash'];

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('Le journal d\'audit est en écriture seule.'));
        static::deleting(fn (): never => throw new LogicException('Le journal d\'audit est en écriture seule.'));
    }

    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'changes' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
