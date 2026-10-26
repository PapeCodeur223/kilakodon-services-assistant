<?php

namespace App\Domain\Crm\Models;

use App\Domain\Crm\Enums\PreferredChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $company_id
 * @property string $full_name
 * @property string|null $phone_e164
 * @property string|null $whatsapp_e164
 * @property string|null $email
 * @property string|null $job_title
 * @property string|null $city
 * @property string $country_code
 * @property PreferredChannel $preferred_channel
 * @property bool $marketing_opt_in
 * @property Carbon|null $anonymized_at
 * @property int|null $merged_into_id
 * @property-read Company|null $company
 */
class Contact extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'full_name', 'phone_e164', 'whatsapp_e164', 'email', 'job_title',
        'city', 'country_code', 'preferred_channel', 'marketing_opt_in',
    ];

    protected $attributes = [
        'country_code' => 'ML',
        'preferred_channel' => 'whatsapp',
        'marketing_opt_in' => false,
    ];

    protected function casts(): array
    {
        return [
            'preferred_channel' => PreferredChannel::class,
            'marketing_opt_in' => 'boolean',
            'anonymized_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasMany<Lead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * @return HasMany<Consent, $this>
     */
    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }
}
