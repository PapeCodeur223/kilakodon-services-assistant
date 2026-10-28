<?php

namespace App\Domain\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string|null $sector_code
 * @property string|null $size_band
 * @property string|null $sites_band
 * @property string|null $city
 */
class Company extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'sector_code', 'size_band', 'sites_band', 'city'];

    /**
     * @return HasMany<Contact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }
}
