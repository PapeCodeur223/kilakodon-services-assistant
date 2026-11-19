<?php

use App\Domain\DemoLab\Actions\CreateDemoWorkspace;
use App\Domain\DemoLab\Actions\RecordDemoSale;
use App\Domain\DemoLab\Enums\Mission;
use App\Domain\DemoLab\Enums\PaymentMethod;
use App\Domain\DemoLab\Models\DemoCustomer;
use App\Domain\DemoLab\Models\DemoProduct;
use App\Domain\DemoLab\Models\DemoSale;
use App\Domain\DemoLab\Models\DemoWorkspace;
use App\Domain\DemoLab\Support\WorkspaceContext;
use App\Livewire\DemoLab\Shop;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

/**
 * @return array{workspace: DemoWorkspace, token: string}
 */
function labSpace(string $ip = '10.1.0.1'): array
{
    return app(CreateDemoWorkspace::class)->handle($ip);
}

function labSku(DemoWorkspace $workspace, string $sku): DemoProduct
{
    return app(WorkspaceContext::class)->run($workspace, fn (): DemoProduct => DemoProduct::query()->where('sku', $sku)->sole());
}

it('présente les 3 démos : Boutique interactive et deux démos guidées', function () {
    $this->get('/demo-lab')->assertOk()
        ->assertSee('Boutique')->assertSee('Interactif')
        ->assertSee('Gestion scolaire')->assertSee('Boulangerie')
        ->assertSee('Démo guidée')->assertSee('Visite en captures')->assertDontSee('Bientôt disponible')
        ->assertSee(route('lab.retail'));
});

it('crée un espace à la première visite, le reprend ensuite par le cookie, et n’est pas indexé', function () {
    $response = $this->get('/demo-lab/boutique')->assertOk()
        ->assertSee('Démo — données fictives, réinitialisées toutes les 24 h', false)
        ->assertSee('noindex', false)
        ->assertCookie('kh_lab');

    expect(DemoWorkspace::query()->count())->toBe(1);

    $token = $response->getCookie('kh_lab')?->getValue();
    $this->withCookie('kh_lab', (string) $token)->get('/demo-lab/boutique')->assertOk();

    expect(DemoWorkspace::query()->count())->toBe(1);
});

it('autorise Livewire et Alpine dans le navigateur sur la boutique (CSP avec nonce, D-014)', function () {
    $csp = (string) $this->get('/demo-lab/boutique')->assertOk()->headers->get('Content-Security-Policy');

    expect($csp)->toContain("script-src 'self' 'nonce-")
        ->toContain("'unsafe-eval'")
        ->not->toContain("'unsafe-inline' 'unsafe-eval'");

    // Pages de contenu du Démo Lab : pas de Livewire, donc pas d'assouplissement.
    foreach (['/demo-lab', '/demo-lab/boulangerie'] as $path) {
        expect((string) $this->get($path)->headers->get('Content-Security-Policy'))->not->toContain('unsafe-eval');
    }
});

it('oriente vers le diagnostic quand la limite de 3 espaces par jour est atteinte', function () {
    foreach (range(1, 3) as $i) {
        labSpace('127.0.0.1');
    }

    $this->get('/demo-lab/boutique')->assertStatus(429)
        ->assertSee('Faire mon diagnostic gratuit')
        ->assertSee('entry=lab%3Aretail', false);
});

it('affiche la facture fictive de son espace avec filigrane et coche la mission, jamais celle d’un autre espace', function () {
    ['workspace' => $mine, 'token' => $token] = labSpace('10.1.0.1');
    ['workspace' => $other] = labSpace('10.1.0.2');
    app(RecordDemoSale::class)->handle($other, [labSku($other, 'RIZ-25')->id => 1], PaymentMethod::Cash); // DEMO-0031 chez l'autre

    $this->withCookie('kh_lab', $token)->get('/demo-lab/boutique/factures/DEMO-0001')->assertOk()
        ->assertSee('DÉMO — DOCUMENT FICTIF')
        ->assertSee('Document fictif — sans valeur')
        ->assertSee('FCFA');

    expect($mine->refresh()->hasCompleted(Mission::Invoice))->toBeTrue();

    // DEMO-0031 n'existe que dans l'autre espace : introuvable ici.
    $this->withCookie('kh_lab', $token)->get('/demo-lab/boutique/factures/DEMO-0031')->assertNotFound();
    // Sans cookie, aucune facture.
    $this->defaultCookies = [];
    $this->get('/demo-lab/boutique/factures/DEMO-0001')->assertNotFound();
});

it('présente les démos guidées en captures commentées, indexables, et renvoie 404 sinon (LAB-02)', function () {
    $this->get('/demo-lab/gestion-scolaire')->assertOk()
        ->assertSee('Visite en captures')->assertSee('noms des élèves masqués')
        ->assertSee('images/demos/ecole-tableau-de-bord.webp', false)
        ->assertSee('width="1600"', false)
        ->assertDontSee('Bientôt disponible')
        ->assertDontSee('noindex', false)
        ->assertSee('entry=lab%3Agestion-scolaire', false);
    $this->get('/demo-lab/boulangerie')->assertOk()->assertSee('images/demos/boulangerie-prises-revendeurs.webp', false);
    $this->get('/demo-lab/inconnue')->assertNotFound();

    foreach (['ecole-tableau-de-bord', 'ecole-paiements-alertes', 'ecole-annees-academiques', 'boulangerie-tableau-de-bord', 'boulangerie-prises-revendeurs', 'boulangerie-menu-replie'] as $file) {
        expect(public_path("images/demos/{$file}.webp"))->toBeFile();
    }
});

it('encaisse une vente depuis la caisse, alerte au seuil et propose la conversion après 2 missions', function () {
    Mail::fake();
    ['workspace' => $workspace, 'token' => $token] = labSpace();
    $oil = labSku($workspace, 'HUI-01'); // stock 7, seuil 6

    Livewire::withCookie('kh_lab', $token)
        ->test(Shop::class, ['workspace' => $workspace])
        ->assertSee('Faites une vente')
        ->assertSee('Bientôt bas')
        ->assertDontSee('Vous voulez ça pour votre entreprise ?')
        ->call('add', $oil->id)
        ->call('add', $oil->id)
        ->set('payment', 'mobile_money')
        ->call('checkout')
        ->assertSet('cart', [])
        ->assertSee('Vente DEMO-0031 encaissée.')
        ->assertDispatched('lab-alert')
        ->assertSee('Vous voulez ça pour votre entreprise ?')
        ->assertSee('entry=lab%3Aretail', false);

    expect(labSku($workspace, 'HUI-01')->stock_qty)->toBe(5);
    Mail::assertNothingSent();
});

it('refuse d’ajouter plus que le stock et exige un client pour le crédit', function () {
    ['workspace' => $workspace, 'token' => $token] = labSpace();
    $javel = labSku($workspace, 'JAV-01'); // stock 3

    Livewire::withCookie('kh_lab', $token)
        ->test(Shop::class, ['workspace' => $workspace])
        ->call('add', $javel->id)->call('add', $javel->id)->call('add', $javel->id)->call('add', $javel->id)
        ->assertSet('cart', [$javel->id => 3])
        ->assertSee('Stock insuffisant')
        ->set('payment', 'credit')
        ->call('checkout')
        ->assertSee('Choisissez un client pour une vente à crédit.');

    expect(app(WorkspaceContext::class)->run($workspace, fn () => DemoSale::query()->count()))->toBe(30);
});

it('fait un réassort depuis l’onglet Stock et affiche le tableau de bord étiqueté Démo', function () {
    ['workspace' => $workspace, 'token' => $token] = labSpace();
    $soap = labSku($workspace, 'SAV-ME');

    Livewire::withCookie('kh_lab', $token)
        ->test(Shop::class, ['workspace' => $workspace])
        ->call('show', 'stock')
        ->assertSee('Stock bas')
        ->set("restock.{$soap->id}", 10)
        ->call('restockProduct', $soap->id)
        ->assertSee('Réassort enregistré')
        ->call('show', 'tableau')
        ->assertSee('Chiffres fictifs · Démo')
        ->assertSee('Meilleurs produits')
        ->call('show', 'ventes')
        ->assertSee('DEMO-0030')
        ->assertSee(route('lab.invoice', 'DEMO-0030'));

    expect(labSku($workspace, 'SAV-ME')->stock_qty)->toBe(17);
});

it('bascule en « espace expiré » si le cookie ne correspond plus à l’espace, sans rien écrire', function () {
    ['workspace' => $workspace] = labSpace('10.1.0.1');
    ['token' => $otherToken] = labSpace('10.1.0.2');
    $rice = labSku($workspace, 'RIZ-25');

    Livewire::withCookie('kh_lab', $otherToken)
        ->test(Shop::class, ['workspace' => $workspace])
        ->call('add', $rice->id)
        ->call('checkout')
        ->assertSee('Cet espace de démonstration a expiré');

    expect(app(WorkspaceContext::class)->run($workspace, fn () => DemoSale::query()->count()))->toBe(30);
});

it('refuse de changer l’espace verrouillé du composant', function () {
    ['workspace' => $workspace, 'token' => $token] = labSpace('10.1.0.1');
    ['workspace' => $other] = labSpace('10.1.0.2');

    expect(fn () => Livewire::withCookie('kh_lab', $token)->test(Shop::class, ['workspace' => $workspace])->set('workspaceId', $other->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('refuse une vente avec un client d’un autre espace', function () {
    ['workspace' => $workspace, 'token' => $token] = labSpace('10.1.0.1');
    ['workspace' => $other] = labSpace('10.1.0.2');
    $foreignCustomer = app(WorkspaceContext::class)->run($other, fn () => DemoCustomer::query()->value('id'));

    Livewire::withCookie('kh_lab', $token)
        ->test(Shop::class, ['workspace' => $workspace])
        ->call('add', labSku($workspace, 'SEL-01')->id)
        ->set('customerId', $foreignCustomer)
        ->call('checkout')
        ->assertSee('Client introuvable.');

    expect(app(WorkspaceContext::class)->run($workspace, fn () => DemoSale::query()->count()))->toBe(30);
});

it('planifie la purge du Démo Lab toutes les heures', function () {
    Artisan::call('schedule:list');

    expect(Artisan::output())->toMatch('/^\s*0\s+\*\s+\*\s+\*\s+\*\s+php artisan kh:lab-purge/m');
});
