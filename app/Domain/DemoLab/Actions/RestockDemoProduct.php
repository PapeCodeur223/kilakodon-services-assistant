<?php

namespace App\Domain\DemoLab\Actions;

use App\Domain\DemoLab\Enums\MovementType;
use App\Domain\DemoLab\Exceptions\InvalidDemoOperation;
use App\Domain\DemoLab\Models\DemoProduct;
use App\Domain\DemoLab\Models\DemoStockMovement;
use App\Domain\DemoLab\Models\DemoWorkspace;
use App\Domain\DemoLab\Support\WorkspaceContext;
use Illuminate\Support\Facades\DB;

/**
 * Réassort d'un produit : stock augmenté et mouvement « restock » tracé.
 */
class RestockDemoProduct
{
    public const MAX_QTY = 9999;

    public function __construct(
        private readonly WorkspaceContext $context,
        private readonly ConsumeDemoAction $consume,
    ) {}

    public function handle(DemoWorkspace $workspace, int $productId, int $qty): DemoProduct
    {
        if ($qty < 1 || $qty > self::MAX_QTY) {
            throw new InvalidDemoOperation('Indiquez une quantité entre 1 et '.self::MAX_QTY.'.');
        }

        return $this->context->run($workspace, fn (): DemoProduct => DB::transaction(function () use ($workspace, $productId, $qty): DemoProduct {
            $product = DemoProduct::query()->whereKey($productId)->lockForUpdate()->first()
                ?? throw new InvalidDemoOperation('Produit introuvable.');

            $this->consume->handle($workspace);

            $product->stock_qty += $qty;
            $product->save();
            DemoStockMovement::query()->create([
                'product_id' => $product->id,
                'type' => MovementType::Restock,
                'qty' => $qty,
                'reason' => 'Réassort',
            ]);

            return $product;
        }));
    }
}
