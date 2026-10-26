<?php

namespace App\Http\Controllers\Public;

use App\Domain\Content\Models\Page;
use App\Domain\Content\Services\ContentFinder;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function show(ContentFinder $content, string $slug): View
    {
        /** @var Page $page */
        $page = $content->bySlug(Page::class, $slug);

        return view('pages.page', ['page' => $page]);
    }
}
