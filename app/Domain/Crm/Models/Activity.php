<?php

namespace App\Domain\Crm\Models;

use App\Domain\Crm\Enums\ActivityType;
use App\Domain\Platform\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Activité d'un lead (CRM-04) : note, appel, WhatsApp, e-mail, rendez-vous, tâche, événement système.
 * Toujours interne : jamais exposée publiquement.
 *
 * @property int $id
 * @property int $lead_id
 * @property int|null $user_id
 * @property ActivityType $type
 * @property string $body
 * @property Carbon|null $due_at
 * @property Carbon|null $done_at
 * @property Carbon|null $created_at
 * @property-read User|null $user
 * @property-read Lead $lead
 */
class Activity extends Model
{
    protected $fillable = ['lead_id', 'user_id', 'type', 'body', 'due_at', 'done_at'];

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'due_at' => 'datetime',
            'done_at' => 'datetime',
        ];
    }

    public function isOpenTask(): bool
    {
        return $this->type === ActivityType::Task && $this->done_at === null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
