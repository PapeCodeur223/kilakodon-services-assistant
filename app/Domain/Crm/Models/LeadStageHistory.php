<?php

namespace App\Domain\Crm\Models;

use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Platform\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Historique des étapes d'un lead (CRM-02) : auteur, date, motif. Écriture seule.
 *
 * @property int $id
 * @property int $lead_id
 * @property LeadStage|null $from_stage
 * @property LeadStage $to_stage
 * @property int|null $user_id
 * @property string|null $reason
 * @property Carbon|null $created_at
 * @property-read User|null $user
 */
class LeadStageHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'lead_stage_history';

    protected $fillable = ['lead_id', 'from_stage', 'to_stage', 'user_id', 'reason'];

    protected function casts(): array
    {
        return [
            'from_stage' => LeadStage::class,
            'to_stage' => LeadStage::class,
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('L\'historique des étapes est en écriture seule.'));
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
