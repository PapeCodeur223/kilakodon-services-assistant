<?php

namespace App\Domain\Content\Models;

use App\Domain\Content\Enums\ArticleCategory;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Platform\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property ContentStatus $status
 * @property Carbon|null $published_at
 * @property Carbon|null $updated_at
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property string $title
 * @property string $excerpt
 * @property string $body
 * @property ArticleCategory $category
 * @property int|null $author_id
 * @property int $reading_minutes
 * @property string|null $cover_path
 * @property-read User|null $author
 */
class Article extends ContentModel
{
    protected $fillable = [
        'slug', 'seo_title', 'seo_description', 'og_image_path', 'sort',
        'title', 'excerpt', 'body', 'category', 'author_id', 'cover_path',
    ];

    protected static function booted(): void
    {
        // Temps de lecture calculé (environ 200 mots par minute), jamais saisi à la main.
        static::saving(function (Article $article): void {
            $words = count(preg_split('/\s+/u', trim(strip_tags($article->body))) ?: []);
            $article->reading_minutes = max(1, (int) ceil($words / 200));
        });
    }

    protected function casts(): array
    {
        return [
            'category' => ArticleCategory::class,
            'reading_minutes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function authorName(): string
    {
        return $this->author !== null ? $this->author->name : "L'équipe Kilakodon";
    }

    public function contentFields(): array
    {
        return ['title', 'excerpt', 'body', 'category', 'author_id', 'cover_path'];
    }

    public function publicUrl(): string
    {
        return route('articles.show', $this->slug);
    }
}
