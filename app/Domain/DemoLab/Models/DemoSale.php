<?php

namespace App\Domain\DemoLab\Models;

use App\Domain\DemoLab\Enums\PaymentMethod;
use App\Domain\DemoLab\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Vente fictive (`DEMO-0001`).
 *
 * @property int $id
 * @property string $number
 * @property int|null $customer_id
 * @property PaymentMethod $payment_method
 * @property int $total_xof
 * @property Carbon $sold_at
 * @property-read DemoCustomer|null $customer
 * @property-read Collection<int, DemoSaleItem> $items
 */
class DemoSale extends Model
{
    use BelongsToWorkspace;

    protected $fillable = ['number', 'customer_id', 'payment_method', 'total_xof', 'sold_at'];

    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'total_xof' => 'integer',
            'sold_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DemoCustomer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(DemoCustomer::class, 'customer_id');
    }

    /**
     * @return HasMany<DemoSaleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(DemoSaleItem::class, 'sale_id');
    }
}
