<?php

namespace App\Http\Controllers\Public;

use App\Domain\Brief\Services\BriefAccess;
use App\Domain\Crm\Actions\RequestAppointment;
use App\Domain\Crm\Enums\AppointmentChannel;
use App\Domain\Crm\Enums\SlotPeriod;
use App\Domain\Platform\Services\Settings;
use App\Http\Controllers\Controller;
use App\Http\Requests\AppointmentFormRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Demande de rendez-vous (RDV-01, UX §5.7) : formulaire serveur, utilisable sans JavaScript.
 */
class AppointmentController extends Controller
{
    public function show(Request $request, BriefAccess $access, Settings $settings): View
    {
        $token = $request->query('brief');
        $brief = is_string($token) ? $access->find($token) : null;
        $brief = $brief?->isAccessible() ? $brief : null;
        $declared = $brief?->currentVersion()->declared['contact'] ?? [];

        return view('pages.appointment', [
            'channels' => AppointmentChannel::options(),
            'periods' => SlotPeriod::options(),
            'consentTexts' => (array) $settings->get('consent.texts', []),
            'briefToken' => $brief !== null ? $token : null,
            'briefReference' => $brief?->reference,
            'prefill' => [
                'name' => $declared['full_name'] ?? null,
                'phone' => $declared['phone'] ?? null,
                'email' => $declared['email'] ?? null,
                'company' => $declared['company'] ?? null,
                'topic' => $brief !== null ? 'Échanger sur mon brief '.$brief->reference : null,
            ],
            'minDate' => now(config('kh.display_timezone'))->addDay()->toDateString(),
            'maxDate' => now(config('kh.display_timezone'))->addMonths(3)->toDateString(),
        ]);
    }

    public function store(AppointmentFormRequest $request, RequestAppointment $requestAppointment): RedirectResponse
    {
        $requestAppointment->handle($request->toAppointmentRequest());

        // UX §5.7 : jamais « RDV confirmé » tant qu'un humain n'a pas confirmé (RDV-01).
        return redirect()->route('appointment')->with('appointment.success', 'Demande reçue — un conseiller vous confirme le créneau.');
    }
}
