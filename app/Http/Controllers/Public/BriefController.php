<?php

namespace App\Http\Controllers\Public;

use App\Domain\Brief\Services\BriefAccess;
use App\Domain\Brief\Services\BriefPdf;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

/**
 * Brief privé (`/brief/{token}`) et son PDF (BRF-04). Token inconnu : 404 ;
 * lien expiré ou révoqué : page « lien expiré » avec les moyens de contact.
 */
class BriefController extends Controller
{
    public function show(string $token, BriefAccess $access): BaseResponse
    {
        $brief = $access->find($token) ?? abort(404);

        if (! $brief->isAccessible()) {
            return response()->view('pages.brief-expired', ['reference' => $brief->reference], 410);
        }

        return response()->view('pages.brief', ['brief' => $brief, 'token' => $token]);
    }

    public function pdf(string $token, BriefAccess $access, BriefPdf $pdf): BaseResponse
    {
        $brief = $access->find($token) ?? abort(404);

        if (! $brief->isAccessible()) {
            return response()->view('pages.brief-expired', ['reference' => $brief->reference], 410);
        }

        return new Response($pdf->render($brief), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="brief-'.$brief->reference.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
