<?php

namespace App\Http\Controllers\Public;

use App\Domain\Content\Models\CaseStudy;
use App\Domain\Content\Models\Sector;
use App\Domain\Content\Services\ContentFinder;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CaseStudyController extends Controller
{
    public function index(Request $request, ContentFinder $content): View
    {
        $all = $content->query(CaseStudy::class)->with(['sector', 'services'])->get();

        // Filtres par secteur (lien simple « ?secteur=code », sans JavaScript), limités aux secteurs présents.
        $sectors = $all->pluck('sector')->filter()->unique('id')
            ->map(fn (Sector $sector): array => [
                'code' => $sector->code,
                'name' => $sector->name,
                'icon' => $sector->icon,
                'count' => $all->where('sector_id', $sector->id)->count(),
            ])
            ->sortByDesc('count')->values();

        $selected = $request->query('secteur');
        $selected = is_string($selected) && $sectors->contains('code', $selected) ? $selected : null;

        return view('pages.cases.index', [
            'caseStudies' => $selected ? $all->filter(fn (CaseStudy $c): bool => $c->sector?->code === $selected)->values() : $all,
            'total' => $all->count(),
            'sectors' => $sectors,
            'selected' => $selected,
        ]);
    }

    public function show(ContentFinder $content, string $slug): View
    {
        /** @var CaseStudy $caseStudy */
        $caseStudy = $content->bySlug(CaseStudy::class, $slug);
        $caseStudy->load(['sector', 'services']);

        // Autres réalisations visibles, celles du même secteur d'abord.
        $related = $content->query(CaseStudy::class)->with('sector')
            ->whereKeyNot($caseStudy->id)->get()
            ->sortBy(fn (CaseStudy $c): int => $c->sector_id === $caseStudy->sector_id ? 0 : 1)
            ->take(3)->values();

        return view('pages.cases.show', ['caseStudy' => $caseStudy, 'related' => $related]);
    }
}
