<?php

namespace App\Domain\DemoLab\Models;

use App\Domain\DemoLab\Enums\MovementType;
use App\Domain\DemoLab\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Mouvement de stock (quantité signée : négative pour une vente).
 *
 * @property int $id
 * @property int $product_id
 * @property MovementType $type
 * @property int $qty
 * @property string|null $reason
 * @property Carbon|null $created_at
 * @property-read DemoProduct $product
 */
class DemoStockMovement extends Model
{
    use BelongsToWorkspace;

    protected $fillable = ['product_id', 'type', 'qty', 'reason'];

    protected function casts(): array
    {
        return ['type' => MovementType::class, 'qty' => 'integer'];
    }

    /**
     * @return BelongsTo<DemoProduct, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(DemoProduct::class, 'product_id');
    }
}
