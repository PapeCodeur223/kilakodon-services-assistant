<?php

namespace App\Domain\DemoLab\Support;

use App\Domain\DemoLab\Enums\MovementType;
use App\Domain\DemoLab\Enums\PaymentMethod;
use App\Domain\DemoLab\Models\DemoCustomer;
use App\Domain\DemoLab\Models\DemoProduct;
use App\Domain\DemoLab\Models\DemoSale;
use App\Domain\DemoLab\Models\DemoStockMovement;
use App\Domain\DemoLab\Models\DemoWorkspace;
use Illuminate\Support\Carbon;

/**
 * Jeu de données FICTIF d'une boutique (TRD §8) : 25 produits, 5 clients, 30 ventes sur 14 jours.
 * Prix en FCFA plausibles mais inventés pour la démonstration : ils ne sont ni des tarifs ni des relevés
 * de marché, et l'interface les étiquette « Démo ». Jeu déterministe : chaque espace part du même état.
 */
final class DemoLabFixtures
{
    /**
     * sku, nom, catégorie, unité, prix de vente, coût d'achat, stock, seuil d'alerte.
     * Deux produits sont déjà sous leur seuil (alertes visibles au tableau de bord) et deux juste
     * au-dessus (une petite vente suffit à déclencher la mission « alerte de stock »).
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: int, 5: int, 6: int, 7: int}>
     */
    public const PRODUCTS = [
        ['RIZ-25', 'Riz brisé', 'Céréales', 'sac 25 kg', 17500, 16000, 14, 5],
        ['RIZ-05', 'Riz parfumé', 'Céréales', 'sac 5 kg', 4500, 3900, 22, 6],
        ['FAR-01', 'Farine de blé', 'Céréales', 'sac 1 kg', 600, 500, 40, 10],
        ['PAT-05', 'Pâtes alimentaires', 'Céréales', 'paquet 500 g', 400, 320, 60, 15],
        ['HUI-01', 'Huile végétale', 'Huiles', 'bouteille 1 L', 1250, 1050, 7, 6],
        ['HUI-05', 'Huile végétale', 'Huiles', 'bidon 5 L', 6000, 5300, 9, 4],
        ['SUC-01', 'Sucre en poudre', 'Épicerie', 'sachet 1 kg', 750, 640, 45, 12],
        ['SEL-01', 'Sel iodé', 'Épicerie', 'sachet 1 kg', 200, 150, 50, 10],
        ['TOM-70', 'Tomate concentrée', 'Épicerie', 'boîte 70 g', 150, 110, 120, 30],
        ['BOU-CU', 'Bouillon cube', 'Épicerie', 'boîte de 100', 1500, 1250, 4, 5],
        ['SAR-01', 'Sardines à l’huile', 'Épicerie', 'boîte', 600, 480, 36, 10],
        ['LAI-40', 'Lait en poudre', 'Petit-déjeuner', 'sachet 400 g', 2250, 1950, 18, 6],
        ['THE-VE', 'Thé vert', 'Petit-déjeuner', 'paquet', 600, 470, 30, 8],
        ['CAF-50', 'Café soluble', 'Petit-déjeuner', 'pot 50 g', 1000, 820, 12, 4],
        ['BIS-01', 'Biscuits', 'Petit-déjeuner', 'paquet', 250, 190, 80, 20],
        ['EAU-15', 'Eau minérale', 'Boissons', 'bouteille 1,5 L', 400, 300, 48, 12],
        ['JUS-01', 'Jus de fruits', 'Boissons', 'brique 1 L', 1000, 800, 20, 6],
        ['SAV-ME', 'Savon de ménage', 'Hygiène', 'morceau', 300, 230, 7, 5],
        ['SAV-TO', 'Savon de toilette', 'Hygiène', 'pièce', 350, 270, 40, 10],
        ['LES-05', 'Lessive en poudre', 'Hygiène', 'sachet 500 g', 750, 610, 26, 8],
        ['JAV-01', 'Eau de Javel', 'Hygiène', 'bouteille 1 L', 500, 380, 3, 4],
        ['DEN-01', 'Dentifrice', 'Hygiène', 'tube', 600, 470, 24, 6],
        ['PIL-AA', 'Piles AA', 'Divers', 'paquet de 4', 1000, 780, 15, 5],
        ['BOG-01', 'Bougies', 'Divers', 'paquet', 500, 380, 25, 6],
        ['CRE-1K', 'Crédit téléphonique', 'Divers', 'recharge 1 000 F', 1000, 950, 100, 20],
    ];

    /** Clients fictifs ; numéros neutralisés au format imposé par le schéma. */
    public const CUSTOMERS = [
        ['Awa (cliente fictive)', '+223 00 00 00 01'],
        ['Moussa (client fictif)', '+223 00 00 00 02'],
        ['Fatoumata (cliente fictive)', '+223 00 00 00 03'],
        ['Ibrahim (client fictif)', '+223 00 00 00 04'],
        ['Aminata (cliente fictive)', '+223 00 00 00 05'],
    ];

    public const SALES = 30;

    public const DAYS = 14;

    public function seed(DemoWorkspace $workspace): void
    {
        app(WorkspaceContext::class)->run($workspace, function (): void {
            $products = collect(self::PRODUCTS)->map(fn (array $p): DemoProduct => DemoProduct::query()->create([
                'sku' => $p[0], 'name' => $p[1], 'category' => $p[2], 'unit' => $p[3],
                'price_xof' => $p[4], 'cost_xof' => $p[5], 'stock_qty' => $p[6], 'alert_threshold' => $p[7],
            ]))->values();

            $customers = collect(self::CUSTOMERS)
                ->map(fn (array $c): DemoCustomer => DemoCustomer::query()->create(['name' => $c[0], 'phone_display' => $c[1]]))
                ->values();

            $this->history($products->all(), $customers->all());
        });
    }

    /**
     * Ventes passées réparties sur 14 jours (l'historique n'altère pas le stock actuel affiché).
     *
     * @param  list<DemoProduct>  $products
     * @param  list<DemoCustomer>  $customers
     */
    private function history(array $products, array $customers): void
    {
        $methods = PaymentMethod::cases();
        $start = Carbon::now()->startOfDay()->subDays(self::DAYS - 1);

        for ($i = 0; $i < self::SALES; $i++) {
            $soldAt = $start->copy()->addDays(intdiv($i * self::DAYS, self::SALES))->setTime(8 + ($i * 5) % 11, ($i * 17) % 60);

            if ($soldAt->isFuture()) {
                $soldAt = Carbon::now()->subMinutes(self::SALES - $i);
            }

            $lines = [];
            for ($l = 0; $l < 1 + $i % 3; $l++) {
                $product = $products[($i * 7 + $l * 11) % count($products)];
                $qty = 1 + ($i + $l) % 3;
                $lines[$product->id] = ['product' => $product, 'qty' => ($lines[$product->id]['qty'] ?? 0) + $qty];
            }

            $method = $methods[$i % 5 === 4 ? 2 : $i % 2];
            $total = array_sum(array_map(fn (array $line): int => $line['qty'] * $line['product']->price_xof, $lines));

            $sale = DemoSale::query()->create([
                'number' => sprintf('DEMO-%04d', $i + 1),
                'customer_id' => $method === PaymentMethod::Credit || $i % 4 === 0 ? $customers[$i % count($customers)]->id : null,
                'payment_method' => $method,
                'total_xof' => $total,
                'sold_at' => $soldAt,
            ]);

            foreach ($lines as $line) {
                $sale->items()->create([
                    'product_id' => $line['product']->id,
                    'qty' => $line['qty'],
                    'unit_price_xof' => $line['product']->price_xof,
                    'line_total_xof' => $line['qty'] * $line['product']->price_xof,
                ]);
                $movement = new DemoStockMovement([
                    'product_id' => $line['product']->id,
                    'type' => MovementType::Sale,
                    'qty' => -$line['qty'],
                    'reason' => "Vente {$sale->number}",
                ]);
                $movement->created_at = $soldAt;
                $movement->updated_at = $soldAt;
                $movement->save();
            }
        }
    }
}
