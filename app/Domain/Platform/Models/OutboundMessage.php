<?php

namespace App\Domain\Platform\Models;

use App\Domain\Platform\Enums\OutboundChannel;
use App\Domain\Platform\Enums\OutboundStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property OutboundChannel $channel
 * @property string $to
 * @property string $template
 * @property array<string, mixed> $payload
 * @property OutboundStatus $status
 * @property int $attempts
 * @property string|null $last_error
 * @property Carbon|null $sent_at
 */
class OutboundMessage extends Model
{
    protected $fillable = ['channel', 'to', 'template', 'payload', 'status', 'attempts', 'last_error', 'sent_at'];

    protected $attributes = [
        'status' => 'queued',
        'attempts' => 0,
    ];

    protected function casts(): array
    {
        return [
            'channel' => OutboundChannel::class,
            'status' => OutboundStatus::class,
            'payload' => 'array',
            'attempts' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
