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
            'text' => 'Inscriptions, paiements, bulletins : visite guidée du logiciel utilisé en établissement privé.',
            'sector' => 'school',
            'topics' => ['Inscriptions et dossiers des élèves', 'Suivi des paiements de scolarité', 'Notes et bulletins', 'Tableau de bord de la direction'],
        ],
        'boulangerie' => [
            'title' => 'Boulangerie',
            'icon' => 'croissant',
            'text' => 'Production, ventes et pertes : visite guidée.',
            'sector' => 'bakery_food',
            'topics' => ['Planification de la production', 'Ventes et caisse', 'Suivi des pertes', 'Matières premières et coût de revient'],
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
