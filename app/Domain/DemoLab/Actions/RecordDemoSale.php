<?php

namespace App\Domain\DemoLab\Actions;

use App\Domain\DemoLab\Enums\Mission;
use App\Domain\DemoLab\Enums\MovementType;
use App\Domain\DemoLab\Enums\PaymentMethod;
use App\Domain\DemoLab\Exceptions\InvalidDemoOperation;
use App\Domain\DemoLab\Models\DemoCustomer;
use App\Domain\DemoLab\Models\DemoProduct;
use App\Domain\DemoLab\Models\DemoSale;
use App\Domain\DemoLab\Models\DemoStockMovement;
use App\Domain\DemoLab\Models\DemoWorkspace;
use App\Domain\DemoLab\Support\WorkspaceContext;
use Illuminate\Support\Facades\DB;

/**
 * Encaisse une vente fictive (BACKEND_SCHEMA §7) dans une transaction : vente, lignes, mouvements,
 * décrément du stock. Les prix viennent toujours de la base, jamais du navigateur.
 */
class RecordDemoSale
{
    public const MAX_LINES = 30;

    public const MAX_QTY = 999;

    public function __construct(
        private readonly WorkspaceContext $context,
        private readonly ConsumeDemoAction $consume,
        private readonly CompleteDemoMission $missions,
    ) {}

    /**
     * @param  array<int|string, int|string>  $cart  identifiant du produit => quantité
     *
     * @throws InvalidDemoOperation
     */
    public function handle(DemoWorkspace $workspace, array $cart, PaymentMethod $method, ?int $customerId = null): DemoSaleResult
    {
        $lines = $this->normalize($cart);

        $result = $this->context->run($workspace, function () use ($workspace, $lines, $method, $customerId): DemoSaleResult {
            if ($customerId !== null && ! DemoCustomer::query()->whereKey($customerId)->exists()) {
                throw new InvalidDemoOperation('Client introuvable.');
            }

            if ($method === PaymentMethod::Credit && $customerId === null) {
                throw new InvalidDemoOperation('Choisissez un client pour une vente à crédit.');
            }

            return DB::transaction(function () use ($workspace, $lines, $method, $customerId): DemoSaleResult {
                $products = DemoProduct::query()->whereKey(array_keys($lines))->lockForUpdate()->get()->keyBy('id');

                if ($products->count() !== count($lines)) {
                    throw new InvalidDemoOperation('Un produit du panier est introuvable.');
                }

                foreach ($lines as $id => $qty) {
                    $product = $products[$id];
                    if ($product->stock_qty < $qty) {
                        throw new InvalidDemoOperation("Stock insuffisant pour « {$product->name} » ({$product->stock_qty} restant).");
                    }
                }

                $this->consume->handle($workspace);

                $total = 0;
                foreach ($lines as $id => $qty) {
                    $total += $products[$id]->price_xof * $qty;
                }

                $sale = DemoSale::query()->create([
                    'number' => $this->nextNumber(),
                    'customer_id' => $customerId,
                    'payment_method' => $method,
                    'total_xof' => $total,
                    'sold_at' => now(),
                ]);

                $alerts = [];
                foreach ($lines as $id => $qty) {
                    /** @var DemoProduct $product */
                    $product = $products[$id];
                    $wasLow = $product->isLow();

                    $sale->items()->create([
                        'product_id' => $id,
                        'qty' => $qty,
                        'unit_price_xof' => $product->price_xof,
                        'line_total_xof' => $product->price_xof * $qty,
                    ]);
                    DemoStockMovement::query()->create([
                        'product_id' => $id,
                        'type' => MovementType::Sale,
                        'qty' => -$qty,
                        'reason' => "Vente {$sale->number}",
                    ]);

                    $product->stock_qty -= $qty;
                    $product->save();

                    if (! $wasLow && $product->isLow()) {
                        $alerts[] = $product;
                    }
                }

                return new DemoSaleResult($sale->load('items.product'), $alerts);
            });
        });

        $this->missions->handle($workspace, Mission::Sale);
        if ($result->alerts !== []) {
            $this->missions->handle($workspace, Mission::Alert);
        }

        return $result;
    }

    /**
     * @param  array<int|string, int|string>  $cart
     * @return array<int, int>
     */
    private function normalize(array $cart): array
    {
        $lines = [];
        foreach ($cart as $id => $qty) {
            $id = filter_var($id, FILTER_VALIDATE_INT);
            $qty = filter_var($qty, FILTER_VALIDATE_INT);

            if ($id === false || $qty === false || $qty < 1 || $qty > self::MAX_QTY) {
                throw new InvalidDemoOperation('Quantité invalide dans le panier.');
            }

            $lines[$id] = $qty;
        }

        if ($lines === []) {
            throw new InvalidDemoOperation('Le panier est vide.');
        }

        if (count($lines) > self::MAX_LINES) {
            throw new InvalidDemoOperation('Le panier contient trop de lignes.');
        }

        return $lines;
    }

    private function nextNumber(): string
    {
        $last = DemoSale::query()->lockForUpdate()->orderByDesc('id')->value('number');
        $next = is_string($last) ? ((int) substr($last, 5)) + 1 : 1;

        return sprintf('DEMO-%04d', $next);
    }
}
