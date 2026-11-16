<?php

namespace App\Domain\Assistant\Models;

use App\Domain\Assistant\Enums\Grounding;
use App\Domain\Assistant\Enums\MessageRole;
use App\Domain\Assistant\Enums\SuggestedAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Message d'une conversation (visiteur, assistant ou information de la plateforme).
 * Les citations sont celles validées côté serveur ; la version du prompt est conservée.
 *
 * @property int $id
 * @property string $conversation_id
 * @property MessageRole $role
 * @property string $content
 * @property list<array{chunk_id: int, url: string, title: string}>|null $citations
 * @property Grounding|null $grounding
 * @property SuggestedAction|null $suggested_action
 * @property string|null $model
 * @property string|null $prompt_version
 * @property int|null $tokens_in
 * @property int|null $tokens_out
 * @property int|null $latency_ms
 * @property string|null $error_code
 * @property Carbon|null $created_at
 * @property-read Conversation $conversation
 * @property-read AiFeedback|null $feedback
 */
class Message extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'conversation_id', 'role', 'content', 'citations', 'grounding', 'suggested_action', 'model',
        'prompt_version', 'tokens_in', 'tokens_out', 'latency_ms', 'error_code',
    ];

    protected function casts(): array
    {
        return [
            'role' => MessageRole::class,
            'citations' => 'array',
            'grounding' => Grounding::class,
            'suggested_action' => SuggestedAction::class,
            'tokens_in' => 'integer',
            'tokens_out' => 'integer',
            'latency_ms' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * @return HasOne<AiFeedback, $this>
     */
    public function feedback(): HasOne
    {
        return $this->hasOne(AiFeedback::class);
    }
}
