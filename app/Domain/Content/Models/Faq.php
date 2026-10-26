<?php

namespace App\Domain\Content\Models;

use App\Domain\Content\Concerns\HasRevisions;
use App\Domain\Content\Concerns\Publishable;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Enums\FaqCategory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property ContentStatus $status
 * @property string $question
 * @property string $answer
 * @property FaqCategory $category
 * @property bool $on_home
 * @property int $sort
 */
class Faq extends Model
{
    use HasRevisions;
    use Publishable;

    protected $fillable = ['question', 'answer', 'category', 'on_home', 'sort'];

    protected function casts(): array
    {
        return [
            'category' => FaqCategory::class,
            'on_home' => 'boolean',
        ];
    }

    public function contentFields(): array
    {
        return ['question', 'answer', 'category', 'on_home'];
    }

    /**
     * Les FAQ n'ont pas de slug.
     *
     * @return list<string>
     */
    public function revisionFields(): array
    {
        return [...$this->contentFields(), 'sort'];
    }

    public function publicUrl(): string
    {
        return route('home').'#faq';
    }
}
