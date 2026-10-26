<?php

namespace App\Http\Controllers\Public;

use App\Domain\Content\Support\PendingValidation;
use App\Domain\Crm\Actions\CreateLeadFromContact;
use App\Domain\Crm\Enums\ContactSubject;
use App\Domain\Platform\Services\Settings;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContactFormRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function show(Settings $settings): View
    {
        return view('pages.contact', [
            'subjects' => ContactSubject::options(),
            'consentTexts' => (array) $settings->get('consent.texts', []),
        ]);
    }

    public function store(ContactFormRequest $request, CreateLeadFromContact $createLead, Settings $settings): RedirectResponse
    {
        $createLead->handle($request->toContactRequest());

        // CONTENT §11 ; les horaires ne sont cités qu'une fois confirmés (Q-002).
        $hours = (string) $settings->get('contact.hours');
        $message = PendingValidation::has($hours) || $hours === ''
            ? 'Merci, votre message est bien arrivé. Un conseiller vous recontacte.'
            : "Merci, votre message est bien arrivé. Nous vous répondons pendant nos horaires d’ouverture ({$hours}).";

        return redirect()->route('contact')->with('contact.success', $message);
    }
}
