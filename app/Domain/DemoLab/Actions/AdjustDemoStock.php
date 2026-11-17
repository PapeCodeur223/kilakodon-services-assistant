<?php

namespace App\Domain\DemoLab\Actions;

use App\Domain\DemoLab\Enums\MovementType;
use App\Domain\DemoLab\Exceptions\InvalidDemoOperation;
use App\Domain\DemoLab\Models\DemoProduct;
use App\Domain\DemoLab\Models\DemoStockMovement;
use App\Domain\DemoLab\Models\DemoWorkspace;
use App\Domain\DemoLab\Support\WorkspaceContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ajustement d'inventaire : le stock prend la quantité comptée, l'écart est tracé en mouvement signé.
 */
class AdjustDemoStock
{
    public function __construct(
        private readonly WorkspaceContext $context,
        private readonly ConsumeDemoAction $consume,
    ) {}

    public function handle(DemoWorkspace $workspace, int $productId, int $countedQty, ?string $reason): DemoProduct
    {
        if ($countedQty < 0 || $countedQty > RestockDemoProduct::MAX_QTY) {
            throw new InvalidDemoOperation('Indiquez la quantité comptée (0 ou plus).');
        }

        return $this->context->run($workspace, fn (): DemoProduct => DB::transaction(function () use ($workspace, $productId, $countedQty, $reason): DemoProduct {
            $product = DemoProduct::query()->whereKey($productId)->lockForUpdate()->first()
                ?? throw new InvalidDemoOperation('Produit introuvable.');

            $delta = $countedQty - $product->stock_qty;
            if ($delta === 0) {
                return $product;
            }

            $this->consume->handle($workspace);

            $product->stock_qty = $countedQty;
            $product->save();
            DemoStockMovement::query()->create([
                'product_id' => $product->id,
                'type' => MovementType::Adjustment,
                'qty' => $delta,
                'reason' => Str::limit(trim((string) $reason) ?: 'Ajustement d’inventaire', 160, ''),
            ]);

            return $product;
        }));
    }
}
