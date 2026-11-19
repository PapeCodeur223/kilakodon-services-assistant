<?php

namespace App\Http\Controllers\Public;

use App\Domain\DemoLab\Actions\CompleteDemoMission;
use App\Domain\DemoLab\Actions\CreateDemoWorkspace;
use App\Domain\DemoLab\Actions\FindDemoWorkspace;
use App\Domain\DemoLab\Enums\Mission;
use App\Domain\DemoLab\Exceptions\DemoLimitReached;
use App\Domain\DemoLab\Models\DemoSale;
use App\Domain\DemoLab\Models\DemoWorkspace;
use App\Domain\DemoLab\Support\WorkspaceContext;
use App\Domain\Platform\Actions\RecordAnalyticsEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;

/**
 * Démo Lab (TRD §5.1, §8) : page d'accueil, démo interactive « Boutique » (espace par visiteur repris
 * par le cookie chiffré `kh_lab`), facture fictive imprimable et démos guidées.
 */
class LabController extends Controller
{
    public const COOKIE = 'kh_lab';

    /** Démos guidées (LAB-02) : contenus fournis par Kilakodon, « bientôt disponible » sinon. */
    public const GUIDED = [
        'gestion-scolaire' => [
            'title' => 'Gestion scolaire',
            'icon' => 'graduation-cap',
            'text' => 'Effectifs, paiements de scolarité, impayés et années académiques : visite en captures du logiciel de gestion scolaire.',
            'sector' => 'school',
            'topics' => ['Tableau de bord de la direction', 'Paiements, dépenses et taux de recouvrement', 'Alertes sur les impayés', 'Années académiques et trimestres'],
            'screens' => [
                ['file' => 'ecole-tableau-de-bord', 'width' => 1600, 'height' => 757, 'title' => 'Le tableau de bord de la direction',
                    'caption' => 'Élèves actifs, enseignants, classes et inscriptions de l’année ; solde des caisses, taux de recouvrement, paiements et dépenses du mois ; évolution des finances sur 6 mois et élèves par niveau.'],
                ['file' => 'ecole-paiements-alertes', 'width' => 1600, 'height' => 759, 'title' => 'Paiements, effectifs et impayés',
                    'caption' => 'Répartition des paiements par mode, classes les plus chargées, utilisation de la licence et alertes financières sur les restes à payer (noms des élèves masqués).'],
                ['file' => 'ecole-annees-academiques', 'width' => 1600, 'height' => 760, 'title' => 'Années académiques et trimestres',
                    'caption' => 'Chaque année scolaire a ses dates, son statut (ouverte, clôturée, archivée), ses trimestres et son calendrier.'],
            ],
        ],
        'boulangerie' => [
            'title' => 'Boulangerie',
            'icon' => 'croissant',
            'text' => 'Caisse du jour, production du fourneau et prises des revendeurs : visite en captures du logiciel de boulangerie.',
            'sector' => 'bakery_food',
            'topics' => ['Entrées, sorties et flux net de caisse du jour', 'Rapport d’activité du fourneau', 'Prises du matin et encaissements du soir des revendeurs', 'Stocks et mouvements de stock'],
            'screens' => [
                ['file' => 'boulangerie-tableau-de-bord', 'width' => 1600, 'height' => 762, 'title' => 'La vue d’ensemble du jour',
                    'caption' => 'Entrées du jour (ventes directes et revendeurs), sorties, flux net de caisse et chiffre du mois ; rapport du fourneau : pétrins, pains sortis, ratio pain par pétrin et pétrisseur d’astreinte.'],
                ['file' => 'boulangerie-prises-revendeurs', 'width' => 1600, 'height' => 751, 'title' => 'Les prises des revendeurs',
                    'caption' => 'Pains pris le matin, retours, montant dû, espèces payées et reliquat, par revendeur et par période, avec impression du bilan.'],
                ['file' => 'boulangerie-menu-replie', 'width' => 1600, 'height' => 766, 'title' => 'Plus de place à l’écran',
                    'caption' => 'Le menu se replie pour laisser toute la largeur aux chiffres, pratique sur les petits écrans.'],
            ],
        ],
    ];

    public function index(): Response
    {
        return response()->view('pages.lab.index', ['guided' => self::GUIDED]);
    }

    public function retail(Request $request, FindDemoWorkspace $find, CreateDemoWorkspace $create): Response
    {
        $workspace = $find->handle($this->token($request));

        if ($workspace === null) {
            try {
                ['workspace' => $workspace, 'token' => $token] = $create->handle((string) $request->ip());
            } catch (DemoLimitReached) {
                return response()->view('pages.lab.limit', [], 429);
            }

            Cookie::queue(Cookie::make(self::COOKIE, $token, DemoWorkspace::LIFETIME_HOURS * 60, httpOnly: true, sameSite: 'lax'));
            RecordAnalyticsEvent::server('lab_started');
        }

        return response()->view('pages.lab.retail', ['workspace' => $workspace]);
    }

    public function invoice(Request $request, FindDemoWorkspace $find, WorkspaceContext $context, CompleteDemoMission $missions, string $number): Response
    {
        $workspace = $find->handle($this->token($request)) ?? abort(404);

        // Le global scope limite la recherche à l'espace du visiteur : la facture d'un autre espace est introuvable.
        $sale = $context->run($workspace, fn (): ?DemoSale => DemoSale::query()
            ->with(['items.product', 'customer'])
            ->where('number', mb_substr($number, 0, 16))
            ->first()) ?? abort(404);

        $missions->handle($workspace, Mission::Invoice);
        RecordAnalyticsEvent::server('lab_action', ['action' => 'invoice']);

        return response()->view('pages.lab.invoice', ['sale' => $sale]);
    }

    public function guided(string $slug): Response
    {
        $demo = self::GUIDED[$slug] ?? abort(404);

        return response()->view('pages.lab.guided', ['slug' => $slug, 'demo' => $demo]);
    }

    private function token(Request $request): ?string
    {
        $token = $request->cookie(self::COOKIE);

        return is_string($token) ? $token : null;
    }
}
