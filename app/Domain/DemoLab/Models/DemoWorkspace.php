<?php

namespace App\Domain\DemoLab\Models;

use App\Domain\DemoLab\Enums\Mission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Espace Démo Lab d'un visiteur (TRD §8) : repris par le cookie `kh_lab` (token haché), expire après 24 h.
 *
 * @property string $id
 * @property string $token_hash
 * @property string $scenario
 * @property string $ip_hash
 * @property int $actions_count
 * @property list<string>|null $missions
 * @property Carbon $expires_at
 * @property Carbon|null $created_at
 */
class DemoWorkspace extends Model
{
    use HasUlids;

    public const LIFETIME_HOURS = 24;

    public const MAX_ACTIONS = 500;

    public const MAX_PER_IP_PER_DAY = 3;

    protected $fillable = ['token_hash', 'scenario', 'ip_hash', 'actions_count', 'missions', 'expires_at'];

    protected $hidden = ['token_hash', 'ip_hash'];

    protected function casts(): array
    {
        return [
            'missions' => 'array',
            'expires_at' => 'datetime',
            'actions_count' => 'integer',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('expires_at', '>', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function hasCompleted(Mission $mission): bool
    {
        return in_array($mission->value, $this->missions ?? [], true);
    }

    public function completedCount(): int
    {
        return count(array_intersect($this->missions ?? [], array_column(Mission::cases(), 'value')));
    }

    public function showsConversion(): bool
    {
        return $this->completedCount() >= Mission::CONVERSION_AFTER;
    }
}
