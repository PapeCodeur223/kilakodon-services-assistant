<?php

namespace App\Domain\DemoLab\Models;

use App\Domain\DemoLab\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

/**
 * Produit fictif du Démo Lab.
 *
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property string $category
 * @property string $unit
 * @property int $price_xof
 * @property int $cost_xof
 * @property int $stock_qty
 * @property int $alert_threshold
 */
class DemoProduct extends Model
{
    use BelongsToWorkspace;

    protected $fillable = ['sku', 'name', 'category', 'unit', 'price_xof', 'cost_xof', 'stock_qty', 'alert_threshold'];

    protected function casts(): array
    {
        return [
            'price_xof' => 'integer',
            'cost_xof' => 'integer',
            'stock_qty' => 'integer',
            'alert_threshold' => 'integer',
        ];
    }

    public function isLow(): bool
    {
        return $this->stock_qty <= $this->alert_threshold;
    }

    /** À 2 unités ou moins du seuil : une petite vente suffit à déclencher l’alerte (mission du guide). */
    public function isNearThreshold(): bool
    {
        return ! $this->isLow() && $this->stock_qty - $this->alert_threshold <= 2;
    }
}
