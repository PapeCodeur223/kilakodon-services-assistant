<?php

namespace App\Domain\Crm\Models;

use App\Domain\Crm\Enums\ConsentPurpose;
use App\Domain\Crm\Enums\ConsentSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Consentement versionné, en écriture seule : un retrait crée une nouvelle ligne (PRV-01).
 *
 * @property int $id
 * @property int|null $contact_id
 * @property string|null $visitor_key
 * @property ConsentPurpose $purpose
 * @property bool $granted
 * @property string $text_version
 * @property ConsentSource $source
 * @property string|null $ip_hash
 * @property Carbon|null $created_at
 * @property Carbon|null $withdrawn_at
 */
class Consent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['contact_id', 'visitor_key', 'purpose', 'granted', 'text_version', 'source', 'ip_hash'];

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('Un consentement ne se modifie pas : enregistrez-en un nouveau.'));
        static::deleting(fn (): never => throw new LogicException('Un consentement ne se supprime pas.'));
    }

    protected function casts(): array
    {
        return [
            'purpose' => ConsentPurpose::class,
            'source' => ConsentSource::class,
            'granted' => 'boolean',
            'withdrawn_at' => 'datetime',
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
