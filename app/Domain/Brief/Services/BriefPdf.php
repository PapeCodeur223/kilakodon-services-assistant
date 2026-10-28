<?php

namespace App\Domain\Brief\Services;

use App\Domain\Brief\Models\Brief;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * PDF du brief (BRF-04) à l'identité Kilakodon, mention « document non contractuel ».
 * Généré à la demande depuis la version figée : le même brief produit toujours le même contenu.
 */
class BriefPdf
{
    public function render(Brief $brief): string
    {
        $logo = public_path('images/brand/logo-kilakodon-2x.png');

        return Pdf::loadView('pdf.brief', [
            'brief' => $brief,
            'version' => $brief->currentVersion(),
            'logo' => is_file($logo) ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($logo)) : null,
        ])
            ->setPaper('a4')
            ->setOption(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans'])
            ->output();
    }
}
