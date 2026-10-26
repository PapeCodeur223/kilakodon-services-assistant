<?php

namespace App\Domain\Platform\Models;

use App\Domain\Platform\Concerns\Auditable;
use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Services\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $key
 * @property mixed $value
 * @property int|null $updated_by
 */
class Setting extends Model
{
    use Auditable;

    protected $fillable = ['key', 'value', 'updated_by'];

    protected static function booted(): void
    {
        static::saved(fn () => app(Settings::class)->flush());
        static::deleted(fn () => app(Settings::class)->flush());
    }

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @param  array<string, mixed>  $after
     */
    protected function auditActionForUpdate(array $after): AuditAction
    {
        return AuditAction::SettingChanged;
    }

    /**
     * @return list<string>
     */
    protected function auditExcluded(): array
    {
        return ['created_at', 'updated_at', 'updated_by'];
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    protected function auditableAttributes(array $keys): array
    {
        $attributes = array_intersect_key($this->attributesToArray(), array_flip(array_diff($keys, $this->auditExcluded())));

        // La clé identifie le paramètre dans le journal, même si seule la valeur change.
        return $attributes === [] ? [] : ['key' => $this->key] + $attributes;
    }
}
