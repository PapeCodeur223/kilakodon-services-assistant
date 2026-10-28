<?php

namespace App\Http\Controllers\Public;

use App\Domain\Content\Services\SitemapBuilder;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(SitemapBuilder $sitemap): Response
    {
        return response($sitemap->xml(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * Les espaces privés sont exclus ; hors production, tout le site est fermé aux robots.
     */
    public function robots(): Response
    {
        $lines = app()->isProduction()
            ? [
                'User-agent: *',
                'Disallow: /admin',
                'Disallow: /brief/',
                'Disallow: /diagnostic/reprendre/',
                'Disallow: /demo-lab/boutique',
                'Disallow: /livewire/',
                'Disallow: /api/',
                '',
                'Sitemap: '.route('sitemap'),
            ]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
