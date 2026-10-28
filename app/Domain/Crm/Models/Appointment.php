<?php

namespace App\Domain\Crm\Models;

use App\Domain\Crm\Enums\AppointmentChannel;
use App\Domain\Crm\Enums\AppointmentStatus;
use App\Domain\Crm\Enums\SlotPeriod;
use App\Domain\Platform\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Rendez-vous (RDV-01) : demandé par le prospect, confirmé uniquement par un humain.
 *
 * @property int $id
 * @property int $lead_id
 * @property AppointmentStatus $status
 * @property AppointmentChannel $channel
 * @property string $topic
 * @property list<array{date: string, period: string}> $preferred_slots
 * @property Carbon|null $scheduled_at
 * @property string $timezone
 * @property int|null $confirmed_by
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property-read Lead $lead
 * @property-read User|null $confirmer
 */
class Appointment extends Model
{
    public const MAX_SLOTS = 3;

    protected $fillable = [
        'lead_id', 'status', 'channel', 'topic', 'preferred_slots', 'scheduled_at', 'timezone', 'confirmed_by', 'notes',
    ];

    protected $attributes = [
        'status' => 'requested',
        'timezone' => 'Africa/Bamako',
    ];

    protected function casts(): array
    {
        return [
            'status' => AppointmentStatus::class,
            'channel' => AppointmentChannel::class,
            'preferred_slots' => 'array',
            'scheduled_at' => 'datetime',
        ];
    }

    /**
     * Créneaux souhaités lisibles : « mardi 4 novembre, matin ».
     *
     * @return list<string>
     */
    public function slotLabels(): array
    {
        return array_map(function (array $slot): string {
            $date = Carbon::parse($slot['date'], $this->timezone)->translatedFormat('l j F');
            $period = SlotPeriod::tryFrom($slot['period'])?->label() ?? $slot['period'];

            return $date.', '.mb_strtolower($period);
        }, $this->preferred_slots);
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
