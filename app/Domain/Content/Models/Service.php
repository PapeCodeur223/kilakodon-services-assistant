<?php

namespace App\Domain\Content\Models;

use App\Domain\Content\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Solution (page /solutions/{slug}).
 *
 * @property int $id
 * @property string $slug
 * @property ContentStatus $status
 * @property Carbon|null $published_at
 * @property Carbon|null $updated_at
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property int $sort
 * @property string|null $module_code
 * @property string $title
 * @property string $tagline
 * @property string $icon
 * @property string $problem
 * @property string $audience
 * @property list<array{title: string, text?: string}> $benefits
 * @property list<array{title: string, text?: string}> $features
 * @property list<array{title: string, text?: string}>|null $process
 * @property string|null $limits
 * @property string|null $duration_hint
 * @property string|null $price_hint
 * @property list<array{q: string, a: string}>|null $faq
 */
class Service extends ContentModel
{
    protected $fillable = [
        'slug', 'seo_title', 'seo_description', 'og_image_path', 'sort',
        'module_code', 'title', 'tagline', 'icon', 'problem', 'audience', 'benefits', 'features',
        'process', 'limits', 'duration_hint', 'price_hint', 'faq',
    ];

    protected function casts(): array
    {
        return [
            'benefits' => 'array',
            'features' => 'array',
            'process' => 'array',
            'faq' => 'array',
        ];
    }

    /**
     * @return BelongsToMany<Sector, $this>
     */
    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class)->withPivot('sort')->orderByPivot('sort');
    }

    public function contentFields(): array
    {
        return ['title', 'tagline', 'icon', 'module_code', 'problem', 'audience', 'benefits', 'features', 'process', 'limits', 'duration_hint', 'price_hint', 'faq'];
    }

    public function publicUrl(): string
    {
        return route('solutions.show', $this->slug);
    }

    /**
     * Q-004 : aucun prix affiché par défaut.
     */
    public function priceLabel(): string
    {
        return $this->price_hint ?: 'Sur devis après diagnostic';
    }
}
