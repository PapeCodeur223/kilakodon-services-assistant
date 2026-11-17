<?php

namespace App\Domain\DemoLab\Models;

use App\Domain\DemoLab\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

/**
 * Client fictif du Démo Lab (téléphone au format `+223 00 00 00 00`, jamais un vrai numéro).
 *
 * @property int $id
 * @property string $name
 * @property string $phone_display
 */
class DemoCustomer extends Model
{
    use BelongsToWorkspace;

    protected $fillable = ['name', 'phone_display'];
}
