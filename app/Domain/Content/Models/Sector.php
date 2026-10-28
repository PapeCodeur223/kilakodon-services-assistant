<?php

namespace App\Domain\Content\Models;

use App\Domain\Content\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Secteur (page /secteurs/{slug}) ; `code` = valeur du diagnostic (`?secteur=`).
 *
 * @property int $id
 * @property string $slug
 * @property ContentStatus $status
 * @property Carbon|null $published_at
 * @property Carbon|null $updated_at
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property int $sort
 * @property string $code
 * @property string $name
 * @property string|null $icon
 * @property string $headline
 * @property string $intro
 * @property list<array{title: string, text?: string}> $pain_points
 * @property list<array{title: string, text?: string}> $outcomes
 * @property string|null $demo_slug
 */
class Sector extends ContentModel
{
    protected $fillable = [
        'slug', 'seo_title', 'seo_description', 'og_image_path', 'sort',
        'code', 'name', 'icon', 'headline', 'intro', 'pain_points', 'outcomes', 'demo_slug',
    ];

    protected function casts(): array
    {
        return [
            'pain_points' => 'array',
            'outcomes' => 'array',
        ];
    }

    /**
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)->withPivot('sort')->orderByPivot('sort');
    }

    /**
     * @return HasMany<CaseStudy, $this>
     */
    public function caseStudies(): HasMany
    {
        return $this->hasMany(CaseStudy::class);
    }

    public function contentFields(): array
    {
        return ['name', 'code', 'icon', 'headline', 'intro', 'pain_points', 'outcomes', 'demo_slug'];
    }

    public function publicUrl(): string
    {
        return route('sectors.show', $this->slug);
    }
}
