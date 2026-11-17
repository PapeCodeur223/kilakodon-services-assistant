<?php

namespace App\Domain\DemoLab\Models;

use App\Domain\DemoLab\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sale_id
 * @property int $product_id
 * @property int $qty
 * @property int $unit_price_xof
 * @property int $line_total_xof
 * @property-read DemoProduct $product
 */
class DemoSaleItem extends Model
{
    use BelongsToWorkspace;

    protected $fillable = ['sale_id', 'product_id', 'qty', 'unit_price_xof', 'line_total_xof'];

    protected function casts(): array
    {
        return ['qty' => 'integer', 'unit_price_xof' => 'integer', 'line_total_xof' => 'integer'];
    }

    /**
     * @return BelongsTo<DemoProduct, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(DemoProduct::class, 'product_id');
    }
}
