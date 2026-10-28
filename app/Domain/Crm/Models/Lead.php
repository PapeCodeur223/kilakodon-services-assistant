<?php

namespace App\Domain\Crm\Models;

use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Crm\Enums\LeadType;
use App\Domain\Platform\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
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
 * @property string $idempotency_key
 * @property array<string, string>|null $utm
 * @property Carbon|null $created_at
 * @property-read Contact $contact
 * @property-read Company|null $company
 * @property-read User|null $owner
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
            'utm' => 'array',
            'next_action_at' => 'datetime',
            'first_response_at' => 'datetime',
            'estimated_amount_xof' => 'integer',
        ];
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
}
