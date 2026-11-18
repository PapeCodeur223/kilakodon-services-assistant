<?php

namespace App\Livewire\DemoLab;

use App\Domain\DemoLab\Actions\AdjustDemoStock;
use App\Domain\DemoLab\Actions\FindDemoWorkspace;
use App\Domain\DemoLab\Actions\RecordDemoSale;
use App\Domain\DemoLab\Actions\RestockDemoProduct;
use App\Domain\DemoLab\Enums\Mission;
use App\Domain\DemoLab\Enums\PaymentMethod;
use App\Domain\DemoLab\Exceptions\DemoLimitReached;
use App\Domain\DemoLab\Exceptions\InvalidDemoOperation;
use App\Domain\DemoLab\Models\DemoCustomer;
use App\Domain\DemoLab\Models\DemoProduct;
use App\Domain\DemoLab\Models\DemoSale;
use App\Domain\DemoLab\Models\DemoSaleItem;
use App\Domain\DemoLab\Models\DemoWorkspace;
use App\Domain\DemoLab\Support\WorkspaceContext;
use App\Http\Controllers\Public\LabController;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Démo « Boutique » (LAB-01, UX §5.6) : Caisse · Stock · Ventes · Tableau de bord.
 * Composant mince : toutes les écritures passent par les Actions du Démo Lab. L'espace est relu
 * à chaque requête depuis le cookie `kh_lab` et doit correspondre à l'identifiant verrouillé.
 */
class Shop extends Component
{
    public const TABS = ['caisse' => 'Caisse', 'stock' => 'Stock', 'ventes' => 'Ventes', 'tableau' => 'Tableau de bord'];

    #[Locked]
    public string $workspaceId = '';

    #[Url(as: 'onglet', except: 'caisse')]
    public string $tab = 'caisse';

    public string $category = '';

    /** @var array<int|string, int> identifiant du produit => quantité */
    public array $cart = [];

    public string $payment = 'cash';

    public ?int $customerId = null;

    /** @var array<int|string, int|string|null> */
    public array $restock = [];

    public ?string $error = null;

    public ?string $notice = null;

    #[Locked]
    public bool $expired = false;

    #[Locked]
    public bool $limitReached = false;

    private ?DemoWorkspace $workspace = null;

    public function mount(DemoWorkspace $workspace): void
    {
        $this->workspaceId = $workspace->id;
        $this->tab = array_key_exists($this->tab, self::TABS) ? $this->tab : 'caisse';
        $this->workspace = $workspace;
        app(WorkspaceContext::class)->set($workspace);
    }

    public function hydrate(): void
    {
        $token = request()->cookie(LabController::COOKIE);
        $workspace = app(FindDemoWorkspace::class)->handle(is_string($token) ? $token : null);

        if ($workspace === null || $workspace->id !== $this->workspaceId) {
            $this->expired = true;

            return;
        }

        $this->workspace = $workspace;
        app(WorkspaceContext::class)->set($workspace);
    }

    public function show(string $tab): void
    {
        $this->tab = array_key_exists($tab, self::TABS) ? $tab : 'caisse';
        $this->resetMessages();
    }

    public function add(int $productId): void
    {
        $this->resetMessages();
        $product = $this->guard() ? DemoProduct::query()->find($productId) : null;

        if ($product === null) {
            return;
        }

        $qty = ($this->cart[$productId] ?? 0) + 1;
        if ($qty > $product->stock_qty) {
            $this->error = "Stock insuffisant pour « {$product->name} ».";

            return;
        }

        $this->cart[$productId] = $qty;
    }

    public function decrement(int $productId): void
    {
        $this->resetMessages();
        $qty = ($this->cart[$productId] ?? 0) - 1;

        if ($qty < 1) {
            unset($this->cart[$productId]);

            return;
        }

        $this->cart[$productId] = $qty;
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->customerId = null;
        $this->resetMessages();
    }

    public function checkout(RecordDemoSale $record): void
    {
        $this->resetMessages();
        if (! $this->guard()) {
            return;
        }

        $method = PaymentMethod::tryFrom($this->payment);
        if ($method === null) {
            $this->error = 'Choisissez un moyen de paiement.';

            return;
        }

        try {
            $result = $record->handle($this->workspace(), $this->cart, $method, $this->customerId);
        } catch (InvalidDemoOperation $e) {
            $this->error = $e->getMessage();

            return;
        } catch (DemoLimitReached) {
            $this->limitReached = true;

            return;
        }

        $this->cart = [];
        $this->customerId = null;
        $this->notice = "Vente {$result->sale->number} encaissée.";

        foreach ($result->alerts as $product) {
            $this->dispatch('lab-alert', message: "Stock bas : {$product->name} ({$product->stock_qty} restant, seuil {$product->alert_threshold}).");
        }
    }

    public function restockProduct(int $productId, RestockDemoProduct $restock): void
    {
        $this->resetMessages();
        if (! $this->guard()) {
            return;
        }

        try {
            $product = $restock->handle($this->workspace(), $productId, (int) ($this->restock[$productId] ?? 0));
            $this->notice = "Réassort enregistré : {$product->name} ({$product->stock_qty} en stock).";
            unset($this->restock[$productId]);
        } catch (InvalidDemoOperation $e) {
            $this->error = $e->getMessage();
        } catch (DemoLimitReached) {
            $this->limitReached = true;
        }
    }

    public function adjustProduct(int $productId, AdjustDemoStock $adjust): void
    {
        $this->resetMessages();
        if (! $this->guard()) {
            return;
        }

        $value = $this->restock[$productId] ?? null;
        if ($value === null || $value === '') {
            $this->error = 'Indiquez la quantité comptée.';

            return;
        }

        try {
            $product = $adjust->handle($this->workspace(), $productId, (int) $value, 'Inventaire');
            $this->notice = "Inventaire enregistré : {$product->name} ({$product->stock_qty} en stock).";
            unset($this->restock[$productId]);
        } catch (InvalidDemoOperation $e) {
            $this->error = $e->getMessage();
        } catch (DemoLimitReached) {
            $this->limitReached = true;
        }
    }

    public function render(): View
    {
        if ($this->expired || $this->workspace === null) {
            return view('livewire.demo-lab.shop', ['state' => 'expired']);
        }

        $products = DemoProduct::query()->orderBy('category')->orderBy('name')->get();

        return view('livewire.demo-lab.shop', [
            'state' => $this->limitReached ? 'limit' : 'ready',
            'workspace' => $this->workspace,
            'missions' => Mission::cases(),
            'products' => $products,
            'categories' => $products->pluck('category')->unique()->values(),
            'cartLines' => $this->cartLines($products),
            'customers' => DemoCustomer::query()->orderBy('name')->get(),
            'payments' => PaymentMethod::cases(),
            'sales' => $this->tab === 'ventes' ? DemoSale::query()->with('customer')->withCount('items')->orderByDesc('sold_at')->orderByDesc('id')->limit(20)->get() : collect(),
            'dashboard' => $this->tab === 'tableau' ? $this->dashboard($products) : null,
        ]);
    }

    /**
     * @param  Collection<int, DemoProduct>  $products
     * @return list<array{product: DemoProduct, qty: int, total: int}>
     */
    private function cartLines(Collection $products): array
    {
        $byId = $products->keyBy('id');
        $lines = [];

        foreach ($this->cart as $id => $qty) {
            if ($byId->has($id)) {
                $lines[] = ['product' => $byId[$id], 'qty' => (int) $qty, 'total' => $byId[$id]->price_xof * (int) $qty];
            }
        }

        return $lines;
    }

    /**
     * Ventes des 7 derniers jours, meilleurs produits et alertes (UX §5.6).
     *
     * @param  Collection<int, DemoProduct>  $products
     * @return array{days: list<array{label: string, date: string, total: int, count: int}>, max: int, total: int, count: int, top: list<array{name: string, qty: int, total: int}>, low: Collection<int, DemoProduct>}
     */
    private function dashboard(Collection $products): array
    {
        $from = Carbon::now('Africa/Bamako')->startOfDay()->subDays(6);
        $sales = DemoSale::query()->where('sold_at', '>=', $from->copy()->utc())->get(['id', 'total_xof', 'sold_at']);

        $days = [];
        for ($d = 0; $d < 7; $d++) {
            $day = $from->copy()->addDays($d);
            $ofDay = $sales->filter(fn (DemoSale $s): bool => $s->sold_at->copy()->setTimezone('Africa/Bamako')->isSameDay($day));
            $days[] = [
                'label' => ucfirst($day->locale('fr')->translatedFormat('D')),
                'date' => $day->locale('fr')->translatedFormat('l j F'),
                'total' => (int) $ofDay->sum('total_xof'),
                'count' => $ofDay->count(),
            ];
        }

        $top = DemoSaleItem::query()
            ->whereIn('sale_id', $sales->modelKeys())
            ->selectRaw('product_id, SUM(qty) as qty_sum, SUM(line_total_xof) as total_sum')
            ->groupBy('product_id')
            ->orderByDesc('qty_sum')
            ->limit(5)
            ->get();
        $names = $products->keyBy('id');

        return [
            'days' => $days,
            'max' => max(1, ...array_column($days, 'total')),
            'total' => (int) $sales->sum('total_xof'),
            'count' => $sales->count(),
            'top' => $top->map(fn (DemoSaleItem $row): array => [
                'name' => $names[$row->product_id]->name ?? '—',
                'qty' => (int) $row->getAttribute('qty_sum'),
                'total' => (int) $row->getAttribute('total_sum'),
            ])->all(),
            'low' => $products->filter(fn (DemoProduct $p): bool => $p->isLow())->values(),
        ];
    }

    private function guard(): bool
    {
        return ! $this->expired && ! $this->limitReached && $this->workspace !== null;
    }

    private function workspace(): DemoWorkspace
    {
        return $this->workspace ?? throw new DemoLimitReached('Espace de démonstration indisponible.');
    }

    private function resetMessages(): void
    {
        $this->error = null;
        $this->notice = null;
    }
}
