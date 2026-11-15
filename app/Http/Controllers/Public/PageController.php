<?php

namespace App\Http\Controllers\Public;

use App\Domain\Content\Models\Page;
use App\Domain\Content\Services\ContentFinder;
use App\Http\Controllers\Controller;
use App\View\Support\MethodContent;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function show(ContentFinder $content, string $slug): View
    {
        /** @var Page $page */
        $page = $content->bySlug(Page::class, $slug);

        // Méthode : présentation en frise tant que le texte suit la forme « ## N. Étape ».
        if ($slug === 'methode') {
            $method = MethodContent::parse($page->body);
            if (count($method['steps']) >= 2) {
                return view('pages.method', ['page' => $page, 'method' => $method]);
            }
        }

        return view('pages.page', ['page' => $page]);
    }
}
