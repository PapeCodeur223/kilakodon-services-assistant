<?php

namespace App\Domain\Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $visitor_key
 * @property string|null $path
 * @property array<string, mixed>|null $properties
 * @property array<string, string>|null $utm
 * @property Carbon $occurred_at
 */
class AnalyticsEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'visitor_key', 'path', 'properties', 'utm', 'occurred_at'];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'utm' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
