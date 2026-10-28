<?php

namespace App\Domain\Content\Models;

use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Enums\PageTemplate;
use Illuminate\Support\Carbon;

/**
 * Pages éditoriales : methode, a-propos, mentions-legales, confidentialite, cookies.
 *
 * @property int $id
 * @property string $slug
 * @property ContentStatus $status
 * @property Carbon|null $published_at
 * @property Carbon|null $updated_at
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property string $title
 * @property string $body
 * @property PageTemplate $template
 */
class Page extends ContentModel
{
    protected $fillable = ['slug', 'seo_title', 'seo_description', 'og_image_path', 'sort', 'title', 'body', 'template'];

    protected function casts(): array
    {
        return [
            'template' => PageTemplate::class,
        ];
    }

    public function contentFields(): array
    {
        return ['title', 'body', 'template'];
    }

    public function publicUrl(): string
    {
        return url('/'.$this->slug);
    }
}
