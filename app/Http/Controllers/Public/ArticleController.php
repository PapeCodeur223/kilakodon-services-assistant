<?php

namespace App\Http\Controllers\Public;

use App\Domain\Content\Models\Article;
use App\Domain\Content\Services\ContentFinder;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ArticleController extends Controller
{
    public function index(ContentFinder $content): View
    {
        return view('pages.articles.index', [
            'articles' => $content->query(Article::class)->with('author')->reorder()->latest('published_at')->orderBy('sort')->get(),
        ]);
    }

    public function show(ContentFinder $content, string $slug): View
    {
        /** @var Article $article */
        $article = $content->bySlug(Article::class, $slug);
        $article->load('author');

        return view('pages.articles.show', [
            'article' => $article,
            'related' => $content->query(Article::class)->whereKeyNot($article->id)->where('category', $article->category)->limit(2)->get(),
        ]);
    }
}
