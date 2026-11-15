<?php

namespace App\Domain\Assistant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Consommation quotidienne de l'assistant (jour de Bamako), incrémentée atomiquement à chaque appel.
 *
 * @property int $id
 * @property Carbon $date
 * @property int $requests
 * @property int $tokens_in
 * @property int $tokens_out
 * @property int $cost_estimate_usd_micros
 * @property int $blocked_requests
 */
class AiUsageDaily extends Model
{
    protected $table = 'ai_usage_daily';

    protected $fillable = ['date', 'requests', 'tokens_in', 'tokens_out', 'cost_estimate_usd_micros', 'blocked_requests'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'requests' => 'integer',
            'tokens_in' => 'integer',
            'tokens_out' => 'integer',
            'cost_estimate_usd_micros' => 'integer',
            'blocked_requests' => 'integer',
        ];
    }

    public static function today(): self
    {
        $date = now(config('kh.display_timezone'))->toDateString();

        self::query()->insertOrIgnore(['date' => $date, 'created_at' => now(), 'updated_at' => now()]);

        return self::query()->whereDate('date', $date)->firstOrFail();
    }

    public function costUsd(): float
    {
        return $this->cost_estimate_usd_micros / 1_000_000;
    }
}
