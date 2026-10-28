<?php

namespace App\Domain\Crm\Models;

use App\Domain\Crm\Enums\PrivacyRequestStatus;
use App\Domain\Crm\Enums\PrivacyRequestType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Demande d'exercice des droits (PRV-02). Le traitement (export, anonymisation) arrive au lot 6.
 *
 * @property int $id
 * @property int|null $contact_id
 * @property string $requester_name
 * @property string $requester_channel
 * @property PrivacyRequestType $type
 * @property PrivacyRequestStatus $status
 * @property Carbon|null $verified_at
 * @property int|null $handled_by
 * @property string|null $decision_note
 * @property Carbon|null $completed_at
 */
class PrivacyRequest extends Model
{
    protected $fillable = [
        'contact_id', 'requester_name', 'requester_channel', 'type', 'status', 'verified_at', 'handled_by', 'decision_note', 'completed_at',
    ];

    protected $attributes = [
        'status' => 'received',
    ];

    protected function casts(): array
    {
        return [
            'type' => PrivacyRequestType::class,
            'status' => PrivacyRequestStatus::class,
            'verified_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
