<?php

namespace App\Domain\Assistant\Models;

use App\Domain\Assistant\Enums\ConversationStatus;
use App\Domain\Crm\Models\Lead;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Conversation anonyme avec l'assistant, identifiée par un ULID ; rattachée à un lead après conversion.
 *
 * @property string $id
 * @property string|null $visitor_key
 * @property string $ip_hash
 * @property int|null $lead_id
 * @property ConversationStatus $status
 * @property string|null $entry_path
 * @property int $message_count
 * @property int $tokens_in
 * @property int $tokens_out
 * @property int $cost_estimate_usd_micros
 * @property Carbon|null $last_message_at
 * @property Carbon|null $created_at
 * @property-read Collection<int, Message> $messages
 * @property-read Lead|null $lead
 */
class Conversation extends Model
{
    use HasUlids;

    protected $fillable = [
        'visitor_key', 'ip_hash', 'lead_id', 'status', 'entry_path', 'message_count',
        'tokens_in', 'tokens_out', 'cost_estimate_usd_micros', 'last_message_at',
    ];

    protected $hidden = ['ip_hash'];

    protected $attributes = [
        'status' => 'open',
        'message_count' => 0,
        'tokens_in' => 0,
        'tokens_out' => 0,
        'cost_estimate_usd_micros' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => ConversationStatus::class,
            'message_count' => 'integer',
            'tokens_in' => 'integer',
            'tokens_out' => 'integer',
            'cost_estimate_usd_micros' => 'integer',
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('id');
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
