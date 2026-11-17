<?php

namespace App\Domain\DemoLab\Actions;

use App\Domain\DemoLab\Models\DemoProduct;
use App\Domain\DemoLab\Models\DemoSale;

/**
 * Résultat d'une vente : la vente et les produits qui viennent de franchir leur seuil d'alerte.
 */
final readonly class DemoSaleResult
{
    /**
     * @param  list<DemoProduct>  $alerts
     */
    public function __construct(
        public DemoSale $sale,
        public array $alerts,
    ) {}
}
