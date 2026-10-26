<?php

namespace App\Domain\Platform\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Compteurs annuels (références KLK-{année}-{n}), incrémentés sous verrou.
 *
 * @property int $id
 * @property string $name
 * @property int $year
 * @property int $last_value
 */
class Sequence extends Model
{
    protected $fillable = ['name', 'year', 'last_value'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'last_value' => 'integer',
        ];
    }
}
