<?php

namespace Database\Factories\Content;

use App\Domain\Content\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        return [
            'slug' => 'page-test-'.Str::lower(Str::random(6)),
            'title' => 'Page de test',
            'body' => "## Titre\n\nTexte de la page.",
            'template' => 'default',
        ];
    }
}
