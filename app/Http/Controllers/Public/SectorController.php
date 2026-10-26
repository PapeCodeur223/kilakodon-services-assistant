<?php

namespace App\Http\Controllers\Public;

use App\Domain\Content\Models\CaseStudy;
use App\Domain\Content\Models\Sector;
use App\Domain\Content\Services\ContentFinder;
use App\Domain\Content\Support\ContentPreview;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class SectorController extends Controller
{
    public function show(ContentFinder $content, string $slug): View
    {
        /** @var Sector $sector */
        $sector = $content->bySlug(Sector::class, $slug);

        return view('pages.sectors.show', [
            'sector' => $sector,
            'services' => $sector->services()->visible(ContentPreview::enabled())->get(),
            'caseStudy' => $content->query(CaseStudy::class)->where('sector_id', $sector->id)->first(),
        ]);
    }
}
