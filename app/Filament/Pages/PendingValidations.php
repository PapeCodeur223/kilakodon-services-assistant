<?php

namespace App\Filament\Pages;

use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\CaseStudy;
use App\Domain\Content\Models\ContentModel;
use App\Domain\Content\Models\Faq;
use App\Domain\Content\Models\Page as ContentPage;
use App\Domain\Content\Models\Sector;
use App\Domain\Content\Models\Service;
use App\Domain\Content\Services\PendingValidationReport;
use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\User;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\CaseStudies\CaseStudyResource;
use App\Filament\Resources\Faqs\FaqResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Sectors\SectorResource;
use App\Filament\Resources\Services\ServiceResource;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * « À valider » : les informations que Kilakodon doit confirmer avant la mise en production
 * (même liste que `kh:content-audit --all`), avec un lien vers l'écran où les corriger.
 */
class PendingValidations extends Page
{
    /** @var array<class-string<ContentModel>, class-string> */
    private const RESOURCES = [
        Service::class => ServiceResource::class,
        Sector::class => SectorResource::class,
        CaseStudy::class => CaseStudyResource::class,
        Article::class => ArticleResource::class,
        Faq::class => FaqResource::class,
        ContentPage::class => PageResource::class,
    ];

    protected string $view = 'filament.pages.pending-validations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Contenus';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'À valider';

    protected static ?string $title = 'Informations à valider';

    protected static ?string $slug = 'a-valider';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasRole(UserRole::Admin, UserRole::Editor);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = app(PendingValidationReport::class)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Informations à confirmer avant la mise en production';
    }

    /**
     * Points groupés par élément (une fiche, une page, un paramètre), chacun avec son lien de correction.
     *
     * @return list<array{type: string, element: string, status: string, published: bool, url: string|null, hint: string, items: list<array{field_label: string, excerpt: string}>}>
     */
    public function getGroups(): array
    {
        $groups = [];

        foreach (app(PendingValidationReport::class)->items() as $item) {
            $key = $item['type'].'|'.$item['element'];

            $groups[$key] ??= [
                'type' => $item['type'],
                'element' => $item['element'],
                'status' => $item['status'],
                'published' => $item['published'],
                'url' => $this->editUrl($item),
                'hint' => match ($item['source']) {
                    'home' => 'Texte de l\'accueil géré par l\'équipe technique : envoyez votre réponse, il sera mis à jour.',
                    'setting' => 'À corriger dans Réglages → Paramètres.',
                    default => $item['published'] ? 'Publié : à corriger en priorité.' : 'Brouillon : la publication reste bloquée tant qu\'un point est à valider.',
                },
                'items' => [],
            ];

            $groups[$key]['items'][] = ['field_label' => $item['field_label'], 'excerpt' => $item['excerpt']];
        }

        return array_values($groups);
    }

    /**
     * @param  array{source: string, record: ContentModel|null}  $item
     */
    private function editUrl(array $item): ?string
    {
        if ($item['source'] === 'setting') {
            return Gate::allows('manage-settings') ? Settings::getUrl() : null;
        }

        $record = $item['record'];
        $resource = $record !== null ? (self::RESOURCES[$record::class] ?? null) : null;

        if ($resource === null || auth()->user()?->can('update', $record) !== true) {
            return null;
        }

        return $resource::getUrl('edit', ['record' => $record]);
    }
}
