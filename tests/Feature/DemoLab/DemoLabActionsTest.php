<?php

use App\Domain\DemoLab\Actions\AdjustDemoStock;
use App\Domain\DemoLab\Actions\CompleteDemoMission;
use App\Domain\DemoLab\Actions\CreateDemoWorkspace;
use App\Domain\DemoLab\Actions\FindDemoWorkspace;
use App\Domain\DemoLab\Actions\PurgeDemoWorkspaces;
use App\Domain\DemoLab\Actions\RecordDemoSale;
use App\Domain\DemoLab\Actions\RestockDemoProduct;
use App\Domain\DemoLab\Enums\Mission;
use App\Domain\DemoLab\Enums\MovementType;
use App\Domain\DemoLab\Enums\PaymentMethod;
use App\Domain\DemoLab\Exceptions\DemoLimitReached;
use App\Domain\DemoLab\Exceptions\InvalidDemoOperation;
use App\Domain\DemoLab\Models\DemoCustomer;
use App\Domain\DemoLab\Models\DemoProduct;
use App\Domain\DemoLab\Models\DemoSale;
use App\Domain\DemoLab\Models\DemoSaleItem;
use App\Domain\DemoLab\Models\DemoStockMovement;
use App\Domain\DemoLab\Models\DemoWorkspace;
use App\Domain\DemoLab\Support\DemoLabFixtures;
use App\Domain\DemoLab\Support\WorkspaceContext;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

function labWorkspace(string $ip = '10.0.0.1'): DemoWorkspace
{
    return app(CreateDemoWorkspace::class)->handle($ip)['workspace'];
}

/**
 * @template T
 *
 * @param  Closure(): T  $callback
 * @return T
 */
function inLab(DemoWorkspace $workspace, Closure $callback): mixed
{
    return app(WorkspaceContext::class)->run($workspace, $callback);
}

function labProduct(DemoWorkspace $workspace, string $sku): DemoProduct
{
    return inLab($workspace, fn (): DemoProduct => DemoProduct::query()->where('sku', $sku)->sole());
}

it('crée un espace pré-rempli de données fictives, expirant après 24 h, avec un token stocké haché', function () {
    ['workspace' => $workspace, 'token' => $token] = app(CreateDemoWorkspace::class)->handle('10.0.0.1');

    expect($token)->toMatch('/^[A-Za-z0-9_-]{43}$/')
        ->and($workspace->token_hash)->toBe(hash('sha256', $token))
        ->and($workspace->ip_hash)->not->toContain('10.0.0.1')
        ->and($workspace->expires_at->between(now()->addHours(23), now()->addHours(25)))->toBeTrue();

    inLab($workspace, function (): void {
        expect(DemoProduct::query()->count())->toBe(25)
            ->and(DemoCustomer::query()->count())->toBe(5)
            ->and(DemoSale::query()->count())->toBe(30)
            ->and(DemoSale::query()->min('sold_at'))->toBeGreaterThanOrEqual(now()->subDays(DemoLabFixtures::DAYS)->toDateTimeString())
            ->and(DemoSale::query()->get()->every(fn (DemoSale $s): bool => $s->total_xof === (int) $s->items()->sum('line_total_xof')))->toBeTrue()
            ->and(DemoCustomer::query()->pluck('phone_display')->every(fn (string $p): bool => str_starts_with($p, '+223 00 00 00')))->toBeTrue();
    });
});

it('isole deux espaces simultanés : aucun ne voit ni ne modifie les données de l’autre', function () {
    $a = labWorkspace('10.0.0.1');
    $b = labWorkspace('10.0.0.2');
    $productOfB = labProduct($b, 'RIZ-25');

    app(RecordDemoSale::class)->handle($a, [labProduct($a, 'RIZ-25')->id => 2], PaymentMethod::Cash);

    foreach ([DemoProduct::class, DemoCustomer::class, DemoSale::class, DemoSaleItem::class, DemoStockMovement::class] as $model) {
        $idsA = inLab($a, fn () => $model::query()->pluck('workspace_id')->unique()->all());
        $idsB = inLab($b, fn () => $model::query()->pluck('workspace_id')->unique()->all());
        expect($idsA)->toBe([$a->id])->and($idsB)->toBe([$b->id]);
    }

    expect(inLab($a, fn () => DemoSale::query()->count()))->toBe(31)
        ->and(inLab($b, fn () => DemoSale::query()->count()))->toBe(30)
        ->and(labProduct($b, 'RIZ-25')->stock_qty)->toBe(14)
        // Un identifiant d'un autre espace est introuvable, donc invendable.
        ->and(fn () => app(RecordDemoSale::class)->handle($a, [$productOfB->id => 1], PaymentMethod::Cash))
        ->toThrow(InvalidDemoOperation::class);
});

it('ne renvoie rien et refuse toute écriture sans espace courant', function () {
    labWorkspace();

    expect(DemoProduct::query()->count())->toBe(0)
        ->and(DemoSale::query()->count())->toBe(0)
        ->and(fn () => DemoCustomer::query()->create(['name' => 'X', 'phone_display' => '+223 00 00 00 09']))
        ->toThrow(LogicException::class);
});

it('refuse d’écrire dans un autre espace que l’espace courant', function () {
    $a = labWorkspace('10.0.0.1');
    $b = labWorkspace('10.0.0.2');

    // `workspace_id` n'est pas assignable en masse ; même posé à la main, il est refusé.
    expect(fn () => inLab($a, fn () => DemoCustomer::query()->create(['name' => 'X', 'phone_display' => '+223 00 00 00 09', 'workspace_id' => $b->id])))
        ->toThrow(MassAssignmentException::class)
        ->and(fn () => inLab($a, function () use ($b): void {
            $customer = new DemoCustomer(['name' => 'X', 'phone_display' => '+223 00 00 00 09']);
            $customer->workspace_id = $b->id;
            $customer->save();
        }))->toThrow(LogicException::class)
        ->and(inLab($b, fn () => DemoCustomer::query()->count()))->toBe(5);
});

it('enregistre une vente : lignes, mouvements, stock décrémenté, numéro suivant et alerte au franchissement du seuil', function () {
    $workspace = labWorkspace();
    $oil = labProduct($workspace, 'HUI-01');   // stock 7, seuil 6
    $rice = labProduct($workspace, 'RIZ-25');  // stock 14, seuil 5

    $result = app(RecordDemoSale::class)->handle($workspace, [$oil->id => 2, $rice->id => 1], PaymentMethod::MobileMoney);

    expect($result->sale->number)->toBe('DEMO-0031')
        ->and($result->sale->total_xof)->toBe(2 * 1250 + 17500)
        ->and($result->sale->payment_method)->toBe(PaymentMethod::MobileMoney)
        ->and($result->sale->items)->toHaveCount(2)
        ->and(labProduct($workspace, 'HUI-01')->stock_qty)->toBe(5)
        ->and(labProduct($workspace, 'RIZ-25')->stock_qty)->toBe(13)
        ->and(array_map(fn (DemoProduct $p): string => $p->sku, $result->alerts))->toBe(['HUI-01'])
        ->and(inLab($workspace, fn () => DemoStockMovement::query()->where('reason', 'Vente DEMO-0031')->sum('qty')))->toEqual(-3);

    $workspace->refresh();
    expect($workspace->hasCompleted(Mission::Sale))->toBeTrue()
        ->and($workspace->hasCompleted(Mission::Alert))->toBeTrue()
        ->and($workspace->showsConversion())->toBeTrue();
});

it('n’alerte pas pour un produit déjà sous son seuil avant la vente', function () {
    $workspace = labWorkspace();
    $javel = labProduct($workspace, 'JAV-01'); // stock 3, seuil 4 : déjà en alerte

    $result = app(RecordDemoSale::class)->handle($workspace, [$javel->id => 1], PaymentMethod::Cash);

    expect($result->alerts)->toBe([])->and($workspace->refresh()->hasCompleted(Mission::Alert))->toBeFalse();
});

it('refuse un panier vide, une quantité invalide, un stock insuffisant ou un client inconnu, sans rien écrire', function (Closure $cart) {
    $workspace = labWorkspace();
    [$lines, $customer] = $cart($workspace);

    expect(fn () => app(RecordDemoSale::class)->handle($workspace, $lines, PaymentMethod::Credit, $customer))
        ->toThrow(InvalidDemoOperation::class)
        ->and(inLab($workspace, fn () => DemoSale::query()->count()))->toBe(30);
})->with([
    'panier vide' => [fn (DemoWorkspace $w) => [[], null]],
    'quantité nulle' => [fn (DemoWorkspace $w) => [[labProduct($w, 'SEL-01')->id => 0], null]],
    'stock insuffisant' => [fn (DemoWorkspace $w) => [[labProduct($w, 'JAV-01')->id => 4], null]],
    'client inconnu' => [fn (DemoWorkspace $w) => [[labProduct($w, 'SEL-01')->id => 1], 999999]],
]);

it('exige un client pour une vente à crédit', function () {
    $workspace = labWorkspace();

    expect(fn () => app(RecordDemoSale::class)->handle($workspace, [labProduct($workspace, 'SEL-01')->id => 1], PaymentMethod::Credit))
        ->toThrow(InvalidDemoOperation::class, 'client');
});

it('réassort et ajustement : stock mis à jour et mouvement tracé', function () {
    $workspace = labWorkspace();
    $soap = labProduct($workspace, 'SAV-ME'); // stock 7

    app(RestockDemoProduct::class)->handle($workspace, $soap->id, 20);
    expect(labProduct($workspace, 'SAV-ME')->stock_qty)->toBe(27);

    app(AdjustDemoStock::class)->handle($workspace, $soap->id, 25, 'Inventaire');
    expect(labProduct($workspace, 'SAV-ME')->stock_qty)->toBe(25);

    $movements = inLab($workspace, fn () => DemoStockMovement::query()->where('product_id', $soap->id)->latest('id')->take(2)->get());
    expect($movements->pluck('type')->all())->toBe([MovementType::Adjustment, MovementType::Restock])
        ->and($movements->pluck('qty')->all())->toBe([-2, 20]);

    expect(fn () => app(RestockDemoProduct::class)->handle($workspace, $soap->id, 0))->toThrow(InvalidDemoOperation::class)
        ->and(fn () => app(AdjustDemoStock::class)->handle($workspace, $soap->id, -1, null))->toThrow(InvalidDemoOperation::class);
});

it('bloque l’espace après 500 actions', function () {
    $workspace = labWorkspace();
    DemoWorkspace::query()->whereKey($workspace->id)->update(['actions_count' => DemoWorkspace::MAX_ACTIONS]);

    expect(fn () => app(RestockDemoProduct::class)->handle($workspace, labProduct($workspace, 'SEL-01')->id, 1))
        ->toThrow(DemoLimitReached::class)
        ->and(labProduct($workspace, 'SEL-01')->stock_qty)->toBe(50);
});

it('limite à 3 espaces par IP et par jour', function () {
    foreach (range(1, 3) as $i) {
        labWorkspace('10.0.0.9');
    }

    expect(fn () => labWorkspace('10.0.0.9'))->toThrow(DemoLimitReached::class)
        ->and(labWorkspace('10.0.0.10'))->toBeInstanceOf(DemoWorkspace::class);

    $this->travel(25)->hours();
    expect(labWorkspace('10.0.0.9'))->toBeInstanceOf(DemoWorkspace::class);
});

it('retrouve un espace actif par son token, jamais un espace expiré ou un token invalide', function () {
    ['workspace' => $workspace, 'token' => $token] = app(CreateDemoWorkspace::class)->handle('10.0.0.1');
    $find = app(FindDemoWorkspace::class);

    expect($find->handle($token)?->id)->toBe($workspace->id)
        ->and($find->handle(null))->toBeNull()
        ->and($find->handle('court'))->toBeNull()
        ->and($find->handle(str_repeat('a', 43)))->toBeNull();

    $this->travel(25)->hours();
    expect($find->handle($token))->toBeNull();
});

it('coche une mission une seule fois', function () {
    $workspace = labWorkspace();
    $complete = app(CompleteDemoMission::class);

    $complete->handle($workspace, Mission::Invoice);
    $complete->handle($workspace, Mission::Invoice);

    expect($workspace->refresh()->missions)->toBe(['invoice'])->and($workspace->showsConversion())->toBeFalse();
});

it('purge les espaces expirés et toutes leurs données, et garde les autres', function () {
    $old = labWorkspace('10.0.0.1');
    $this->travel(25)->hours();
    $fresh = labWorkspace('10.0.0.2');

    expect(app(PurgeDemoWorkspaces::class)->handle())->toBe(1)
        ->and(DemoWorkspace::query()->pluck('id')->all())->toBe([$fresh->id])
        ->and(DB::table('demo_products')->where('workspace_id', $old->id)->count())->toBe(0)
        ->and(DB::table('demo_sales')->where('workspace_id', $old->id)->count())->toBe(0)
        ->and(DB::table('demo_products')->where('workspace_id', $fresh->id)->count())->toBe(25);

    $this->artisan('kh:lab-purge')->assertSuccessful();
});

it('n’envoie aucun e-mail ni aucune notification', function () {
    Mail::fake();
    Notification::fake();

    $workspace = labWorkspace();
    app(RecordDemoSale::class)->handle($workspace, [labProduct($workspace, 'HUI-01')->id => 2], PaymentMethod::Cash);
    app(RestockDemoProduct::class)->handle($workspace, labProduct($workspace, 'HUI-01')->id, 10);

    Mail::assertNothingSent();
    Notification::assertNothingSent();
    expect(DB::table('outbound_messages')->count())->toBe(0);
});
