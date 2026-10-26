<?php

namespace App\Http\Controllers\Public;

use App\Domain\Content\Models\CaseStudy;
use App\Domain\Content\Models\Faq;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Models\Service;
use App\Domain\Content\Services\ContentFinder;
use App\Domain\Content\Services\HomeContent;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(ContentFinder $content, HomeContent $home): View
    {
        return view('pages.home', [
            'home' => $home->all(),
            'services' => $content->query(Service::class)->get(),
            'faqs' => $content->query(Faq::class)->where('on_home', true)->limit(6)->get(),
            // Section masquée tant qu'aucune réalisation n'est autorisée et publiée (Q-006).
            'caseStudies' => $content->query(CaseStudy::class)->with('sector')->limit(3)->get(),
            'methodPageVisible' => $content->query(Page::class)->where('slug', 'methode')->exists(),
        ]);
    }
}
