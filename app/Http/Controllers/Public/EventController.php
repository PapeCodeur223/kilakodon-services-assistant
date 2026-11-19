<?php

namespace App\Http\Controllers\Public;

use App\Domain\Platform\Actions\RecordAnalyticsEvent;
use App\Domain\Platform\Analytics\AudienceConsent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * POST /api/events — événements du navigateur (sendBeacon), liste blanche TRD §9. Sans accord pour la
 * mesure d'audience, rien n'est enregistré (204, pour ne pas révéler le choix).
 */
class EventController extends Controller
{
    public function store(Request $request, RecordAnalyticsEvent $record, AudienceConsent $consent): Response
    {
        if (! $consent->granted()) {
            return response()->noContent();
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:48'],
            'props' => ['sometimes', 'array', 'max:12'],
        ]);

        $event = $record->handle($data['name'], (array) ($data['props'] ?? []), fromClient: true);

        return response()->noContent($event !== null ? 204 : 422);
    }
}
