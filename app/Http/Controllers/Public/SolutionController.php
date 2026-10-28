<?php

namespace App\Http\Controllers\Public;

use App\Domain\Content\Models\Service;
use App\Domain\Content\Services\ContentFinder;
use App\Domain\Content\Support\ContentPreview;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class SolutionController extends Controller
{
    public function index(ContentFinder $content): View
    {
        return view('pages.solutions.index', [
            'services' => $content->query(Service::class)->get(),
        ]);
    }

    public function show(ContentFinder $content, string $slug): View
    {
        /** @var Service $service */
        $service = $content->bySlug(Service::class, $slug);

        return view('pages.solutions.show', [
            'service' => $service,
            'sectors' => $service->sectors()->visible(ContentPreview::enabled())->get(),
            'others' => $content->query(Service::class)->whereKeyNot($service->id)->limit(3)->get(),
            'sectorCode' => $service->sectors()->visible(ContentPreview::enabled())->value('code'),
        ]);
    }
}
