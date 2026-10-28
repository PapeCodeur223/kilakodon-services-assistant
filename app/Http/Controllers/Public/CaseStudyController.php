<?php

namespace App\Http\Controllers\Public;

use App\Domain\Content\Models\CaseStudy;
use App\Domain\Content\Services\ContentFinder;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class CaseStudyController extends Controller
{
    public function index(ContentFinder $content): View
    {
        return view('pages.cases.index', [
            'caseStudies' => $content->query(CaseStudy::class)->with('sector')->get(),
        ]);
    }

    public function show(ContentFinder $content, string $slug): View
    {
        /** @var CaseStudy $caseStudy */
        $caseStudy = $content->bySlug(CaseStudy::class, $slug);
        $caseStudy->load(['sector', 'services']);

        return view('pages.cases.show', ['caseStudy' => $caseStudy]);
    }
}
