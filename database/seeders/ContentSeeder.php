<?php

namespace Database\Seeders;

use App\Domain\Content\Concerns\Publishable;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\Faq;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Models\Sector;
use App\Domain\Content\Models\Service;
use App\Domain\Content\Support\PublishGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Contenus initiaux depuis docs/CONTENT.md (fichiers database/seeders/content/*.php).
 * Règle : tout contenu contenant « [À VALIDER] » est créé en brouillon ; les autres sont publiés.
 * Les articles restent toujours en brouillon (relecture humaine obligatoire).
 * Idempotent : un contenu existant (même slug) n'est jamais écrasé.
 */
class ContentSeeder extends Seeder
{
    /** @var array<string, int> */
    private array $serviceIds = [];

    public function run(): void
    {
        PublishGuard::allow(function (): void {
            foreach ($this->data('services') as $i => $row) {
                $service = $this->upsert(Service::class, ['slug' => $row['slug']], [...$row, 'sort' => $i]);
                $this->serviceIds[$row['slug']] = $service->id;
            }

            foreach ($this->data('sectors') as $i => $row) {
                $services = $row['services'];
                unset($row['services']);
                $sector = $this->upsert(Sector::class, ['code' => $row['code']], [...$row, 'sort' => $i]);

                if ($sector->wasRecentlyCreated) {
                    $sector->services()->sync(collect($services)
                        ->mapWithKeys(fn (string $slug, int $sort): array => [$this->serviceIds[$slug] => ['sort' => $sort]])
                        ->all());
                }
            }

            foreach ($this->data('faqs') as $i => $row) {
                $this->upsert(Faq::class, ['question' => $row['question']], [...$row, 'sort' => $i]);
            }

            foreach ($this->data('pages') as $row) {
                $this->upsert(Page::class, ['slug' => $row['slug']], $row);
            }

            foreach ($this->data('articles') as $i => $row) {
                $this->upsert(Article::class, ['slug' => $row['slug']], [...$row, 'sort' => $i], alwaysDraft: true);
            }
        });
    }

    /**
     * @template T of Model
     *
     * @param  class-string<T>  $model
     * @param  array<string, mixed>  $key
     * @param  array<string, mixed>  $attributes
     * @return T
     */
    private function upsert(string $model, array $key, array $attributes, bool $alwaysDraft = false): Model
    {
        /** @var T&Publishable $content */
        $content = $model::query()->firstOrNew($key);

        if ($content->exists) {
            return $content;
        }

        $content->fill($attributes);
        $publish = ! $alwaysDraft && $content->pendingValidationFields() === [];
        $content->status = $publish ? ContentStatus::Published : ContentStatus::Draft;
        $content->published_at = $publish ? now() : null;
        $content->save();

        return $content;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function data(string $file): array
    {
        return require database_path("seeders/content/{$file}.php");
    }
}
