<?php

namespace App\Http\Controllers\Public;

use App\Domain\Diagnostic\Services\DiagnosticSessions;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Pages hôtes du questionnaire (Livewire `diagnostic.wizard`) — TRD §5.1.
 */
class DiagnosticController extends Controller
{
    public function start(Request $request): View
    {
        $entry = $request->query('entry');
        $sector = $request->query('secteur');

        return view('pages.diagnostic', [
            'session' => null,
            'token' => null,
            'entry' => is_string($entry) ? mb_substr($entry, 0, 64) : null,
            'sector' => is_string($sector) ? mb_substr($sector, 0, 48) : null,
        ]);
    }

    public function resume(string $token, DiagnosticSessions $sessions): View
    {
        // Token inconnu, invalide ou session expirée : 404 (DIA-03).
        $session = $sessions->findByToken($token) ?? abort(404);

        return view('pages.diagnostic', ['session' => $session, 'token' => $token, 'entry' => null, 'sector' => null]);
    }
}
