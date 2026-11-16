<?php

namespace App\Domain\Assistant\Models;

use App\Domain\Assistant\Enums\ProposalStatus;
use App\Domain\Assistant\Enums\ProposalType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Action proposée par l'assistant (TRD §7.5) : rien n'est écrit sans confirmation explicite
 * du visiteur. Le payload est figé et haché ; toute modification crée une nouvelle proposition.
 *
 * @property string $id
 * @property string $conversation_id
 * @property int|null $message_id
 * @property ProposalType $type
 * @property array<string, mixed> $payload
 * @property string $payload_hash
 * @property string $idempotency_key
 * @property ProposalStatus $status
 * @property string|null $result_type
 * @property int|null $result_id
 * @property string|null $error
 * @property Carbon $expires_at
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $executed_at
 * @property-read Conversation $conversation
 */
class AiActionProposal extends Model
{
    use HasUlids;

    public const LIFETIME_MINUTES = 30;

    protected $fillable = [
        'conversation_id', 'message_id', 'type', 'payload', 'payload_hash', 'idempotency_key', 'status',
        'error', 'expires_at', 'confirmed_at', 'executed_at',
    ];

    protected $attributes = [
        'status' => 'proposed',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProposalType::class,
            'status' => ProposalStatus::class,
            'payload' => 'array',
            'expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'executed_at' => 'datetime',
        ];
    }

    /**
     * Empreinte du payload : clés triées, encodage stable. Toute altération change le hash.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function hashPayload(array $payload): string
    {
        $normalize = function (mixed $value) use (&$normalize): mixed {
            if (! is_array($value)) {
                return $value;
            }
            if (! array_is_list($value)) {
                ksort($value);
            }

            return array_map($normalize, $value);
        };

        return hash('sha256', (string) json_encode($normalize($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function result(): MorphTo
    {
        return $this->morphTo();
    }
}
