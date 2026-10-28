<?php

namespace App\Domain\Crm\Models;

use App\Domain\Brief\Models\Brief;
use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Crm\Enums\LeadType;
use App\Domain\Crm\Enums\LostReason;
use App\Domain\Platform\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Lead (CRM-02, CRM-03). L'étape ne change que par `ChangeLeadStage` (transition, historique, audit).
 *
 * @property int $id
 * @property int $contact_id
 * @property int|null $company_id
 * @property int|null $brief_id
 * @property LeadType $type
 * @property string $source
 * @property string|null $message
 * @property LeadStage $stage
 * @property int|null $owner_id
 * @property string|null $next_action
 * @property Carbon|null $next_action_at
 * @property int|null $fit_score
 * @property int|null $maturity_level
 * @property string|null $budget_band
 * @property string|null $timeline
 * @property string|null $decision_role
 * @property int|null $estimated_amount_xof
 * @property LostReason|null $lost_reason
 * @property Carbon|null $first_response_at
 * @property string $idempotency_key
 * @property array<string, string>|null $utm
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Contact $contact
 * @property-read Company|null $company
 * @property-read User|null $owner
 * @property-read Brief|null $brief
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, LeadStageHistory> $stageHistory
 * @property-read Collection<int, Appointment> $appointments
 */
class Lead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contact_id', 'company_id', 'brief_id', 'type', 'source', 'message', 'owner_id',
        'next_action', 'next_action_at', 'fit_score', 'maturity_level', 'budget_band', 'timeline',
        'decision_role', 'estimated_amount_xof', 'idempotency_key', 'utm',
    ];

    protected $attributes = [
        'stage' => 'new',
    ];

    protected function casts(): array
    {
        return [
            'type' => LeadType::class,
            'stage' => LeadStage::class,
            'lost_reason' => LostReason::class,
            'utm' => 'array',
            'next_action_at' => 'datetime',
            'first_response_at' => 'datetime',
            'estimated_amount_xof' => 'integer',
            'maturity_level' => 'integer',
            'fit_score' => 'integer',
        ];
    }

    /**
     * « À traiter » (CRM-02) : lead actif sans prochaine action, ou dont la prochaine action est échue.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeToHandle(Builder $query): Builder
    {
        return $query
            ->whereIn('stage', LeadStage::active())
            ->where(fn (Builder $q) => $q->whereNull('next_action')->orWhereNull('next_action_at')->orWhere('next_action_at', '<=', now()));
    }

    /**
     * Aucune réponse humaine encore enregistrée (délai de prise en charge, alerte « non traité »).
     */
    public function isUnanswered(): bool
    {
        return $this->first_response_at === null;
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsTo<Brief, $this>
     */
    public function brief(): BelongsTo
    {
        return $this->belongsTo(Brief::class);
    }

    /**
     * @return HasMany<Activity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->latest()->latest('id');
    }

    /**
     * @return HasMany<LeadStageHistory, $this>
     */
    public function stageHistory(): HasMany
    {
        return $this->hasMany(LeadStageHistory::class)->latest()->latest('id');
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class)->latest();
    }
}
