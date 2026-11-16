<?php

namespace Database\Seeders;

use App\Domain\Content\Enums\AuthorizationStatus;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\CaseStudy;
use App\Domain\Content\Models\ContentModel;
use App\Domain\Content\Models\Faq;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Models\Sector;
use App\Domain\Content\Models\Service;
use App\Domain\Content\Support\PublishGuard;
use Illuminate\Database\Seeder;

/**
 * Contenus initiaux depuis docs/CONTENT.md (fichiers database/seeders/content/*.php).
 * Règle : tout contenu contenant « [À VALIDER] » est créé en brouillon ; les autres sont publiés.
 * Les articles restent toujours en brouillon (relecture humaine obligatoire) ; les réalisations ne sont publiées
 * qu’avec une autorisation client accordée (WEB-03).
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
                /** @var list<string> $services */
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

            // Réalisations : publiées seulement avec l'autorisation écrite du client (WEB-03), sinon brouillon.
            foreach ($this->data('case-studies') as $i => $row) {
                /** @var list<string> $services */
                $services = $row['services'];
                $sector = Sector::query()->where('code', $row['sector'])->value('id');
                $authorization = AuthorizationStatus::tryFrom((string) ($row['authorization_status'] ?? '')) ?? AuthorizationStatus::None;
                $guarded = ['authorization_status' => $authorization, 'authorization_note' => $row['authorization_note'] ?? null];
                unset($row['services'], $row['sector'], $row['authorization_status'], $row['authorization_note']);
                $case = $this->upsert(CaseStudy::class, ['slug' => $row['slug']], [...$row, 'sector_id' => $sector, 'sort' => $i],
                    alwaysDraft: $authorization !== AuthorizationStatus::Granted, guarded: $guarded);

                if ($case->wasRecentlyCreated) {
                    $case->services()->sync(Service::query()->whereIn('slug', $services)->pluck('id')->all());
                }
            }
        });
    }

    /**
     * @template T of ContentModel
     *
     * @param  class-string<T>  $model
     * @param  array<string, mixed>  $key
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $guarded  attributs hors $fillable (autorisation client), posés explicitement
     * @return T
     */
    private function upsert(string $model, array $key, array $attributes, bool $alwaysDraft = false, array $guarded = []): ContentModel
    {
        /** @var T $content */
        $content = $model::query()->firstOrNew($key);

        if ($content->exists) {
            return $content;
        }

        $content->fill($attributes)->forceFill($guarded);
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
