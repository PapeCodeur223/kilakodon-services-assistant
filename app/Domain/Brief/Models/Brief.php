<?php

namespace App\Domain\Brief\Models;

use App\Domain\Brief\Enums\BriefStatus;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Lead;
use App\Domain\Diagnostic\Models\DiagnosticSession;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Brief (BRF-01..04) : référence KLK, lien privé (token haché, 30 jours, révocable),
 * versions figées à chaque validation.
 *
 * @property int $id
 * @property string $reference
 * @property string|null $diagnostic_session_id
 * @property string|null $conversation_id
 * @property int|null $contact_id
 * @property BriefStatus $status
 * @property int $current_version
 * @property string $access_token_hash
 * @property Carbon $access_expires_at
 * @property Carbon|null $access_revoked_at
 * @property Carbon|null $validated_at
 * @property string|null $pdf_path
 * @property Carbon|null $created_at
 * @property-read Contact|null $contact
 * @property-read DiagnosticSession|null $diagnosticSession
 * @property-read Collection<int, BriefVersion> $versions
 * @property-read BriefVersion|null $latestVersion
 * @property-read Lead|null $lead
 */
class Brief extends Model
{
    public const ACCESS_DAYS = 30;

    protected $fillable = [
        'reference', 'diagnostic_session_id', 'conversation_id', 'contact_id', 'status', 'current_version',
        'access_token_hash', 'access_expires_at', 'access_revoked_at', 'validated_at', 'pdf_path',
    ];

    protected $hidden = ['access_token_hash'];

    protected $attributes = [
        'status' => 'draft',
        'current_version' => 1,
    ];

    protected function casts(): array
    {
        return [
            'status' => BriefStatus::class,
            'current_version' => 'integer',
            'access_expires_at' => 'datetime',
            'access_revoked_at' => 'datetime',
            'validated_at' => 'datetime',
        ];
    }

    public function isRevoked(): bool
    {
        return $this->access_revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->access_expires_at->isPast();
    }

    public function isAccessible(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    public function isValidated(): bool
    {
        return $this->status === BriefStatus::Validated;
    }

    public function currentVersion(): BriefVersion
    {
        return $this->versions()->where('version', $this->current_version)->firstOrFail();
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<DiagnosticSession, $this>
     */
    public function diagnosticSession(): BelongsTo
    {
        return $this->belongsTo(DiagnosticSession::class, 'diagnostic_session_id');
    }

    /**
     * @return HasMany<BriefVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(BriefVersion::class)->orderBy('version');
    }

    /**
     * @return HasOne<BriefVersion, $this>
     */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(BriefVersion::class)->ofMany('version', 'max');
    }

    /**
     * @return HasOne<Lead, $this>
     */
    public function lead(): HasOne
    {
        return $this->hasOne(Lead::class);
    }
}
