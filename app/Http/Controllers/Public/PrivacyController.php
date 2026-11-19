<?php

namespace App\Http\Controllers\Public;

use App\Domain\Crm\Actions\SubmitPrivacyRequest;
use App\Domain\Crm\Enums\PrivacyRequestType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PrivacyFormRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * « Gérer mes données » (PRV-02) : formulaire dédié aux demandes de droits, traitées à la main.
 */
class PrivacyController extends Controller
{
    public function show(): View
    {
        return view('pages.privacy-request', ['types' => PrivacyRequestType::publicOptions()]);
    }

    public function store(PrivacyFormRequest $request, SubmitPrivacyRequest $submit): RedirectResponse
    {
        $submit->handle(
            $request->string('name')->toString(),
            $request->string('channel')->toString(),
            PrivacyRequestType::from($request->string('type')->toString()),
            $request->filled('details') ? $request->string('details')->toString() : null,
            $request->ip(),
        );

        return redirect()->route('privacy.form')
            ->with('privacy.success', 'Votre demande est bien enregistrée. Nous vous recontactons pour vérifier votre identité avant de la traiter.');
    }
}
