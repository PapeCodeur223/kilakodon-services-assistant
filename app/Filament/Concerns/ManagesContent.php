<?php

namespace App\Filament\Concerns;

use App\Domain\Content\Actions\ChangeContentStatus;
use App\Domain\Content\Actions\PublishContent;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Exceptions\ContentTransitionException;
use App\Domain\Content\Models\ContentModel;
use App\Domain\Platform\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;

/**
 * Éléments communs aux ressources de contenu : statut, SEO, actions de cycle de vie.
 * Les actions appellent les Actions métier (jamais d'écriture directe du statut).
 */
trait ManagesContent
{
    public static function statusColumn(): TextColumn
    {
        return TextColumn::make('status')
            ->label('Statut')
            ->badge()
            ->formatStateUsing(fn (ContentStatus $state): string => $state->label())
            ->color(fn (ContentStatus $state): string => match ($state) {
                ContentStatus::Published => 'success',
                ContentStatus::Review => 'warning',
                ContentStatus::Archived => 'gray',
                ContentStatus::Draft => 'info',
            })
            ->sortable();
    }

    public static function pendingColumn(): TextColumn
    {
        return TextColumn::make('pending')
            ->label('À valider')
            ->state(fn (ContentModel $record): int => count(static::pending($record)))
            ->badge()
            ->color(fn (int $state): string => $state > 0 ? 'warning' : 'success')
            ->formatStateUsing(fn (int $state): string => $state > 0 ? $state.' champ(s)' : 'Complet')
            ->tooltip(fn (ContentModel $record): ?string => static::pending($record) !== [] ? implode(', ', static::pending($record)) : null);
    }

    public static function statusFilter(): SelectFilter
    {
        return SelectFilter::make('status')->label('Statut')->options(ContentStatus::options());
    }

    public static function seoSection(bool $withSlug = true): Section
    {
        $fields = [
            TextInput::make('seo_title')->label('Titre SEO')->maxLength(70)->helperText('60 caractères conseillés ; « — Kilakodon Services » est ajouté automatiquement.'),
            TextInput::make('seo_description')->label('Meta description')->maxLength(170)->helperText('Entre 140 et 160 caractères.'),
            TextInput::make('sort')->label('Ordre d’affichage')->integer()->minValue(0)->default(0),
        ];

        if ($withSlug) {
            array_unshift($fields, TextInput::make('slug')
                ->label('Adresse (slug)')
                ->required()
                ->maxLength(140)
                ->alphaDash()
                ->unique(ignoreRecord: true)
                ->helperText('Ne la changez pas après publication : les liens existants seraient cassés.'));
        }

        return Section::make('Référencement et affichage')->columns(2)->collapsible()->schema($fields);
    }

    /**
     * @return list<Action|ActionGroup>
     */
    public static function lifecycleActions(): array
    {
        return [
            Action::make('preview')
                ->label('Aperçu')
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->url(fn (ContentModel $record): string => static::publicUrlOf($record), shouldOpenInNewTab: true),
            Action::make('submitForReview')
                ->label('Soumettre à relecture')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->visible(fn (ContentModel $record): bool => static::statusOf($record) === ContentStatus::Draft)
                ->action(fn (ContentModel $record) => static::transition($record, ContentStatus::Review)),
            Action::make('publish')
                ->label('Publier')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Le contenu sera visible du public et indexable.')
                ->visible(fn (ContentModel $record): bool => static::statusOf($record) !== ContentStatus::Published && static::currentUser()?->can('publish', $record) === true)
                ->action(function (ContentModel $record): void {
                    static::run(fn () => app(PublishContent::class)->handle($record, static::currentUserOrFail()), 'Contenu publié');
                }),
            ActionGroup::make([
                Action::make('backToDraft')
                    ->label('Repasser en brouillon')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->requiresConfirmation()
                    ->visible(fn (ContentModel $record): bool => static::statusOf($record) !== ContentStatus::Draft)
                    ->action(fn (ContentModel $record) => static::transition($record, ContentStatus::Draft)),
                Action::make('archive')
                    ->label('Archiver')
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (ContentModel $record): bool => static::statusOf($record) !== ContentStatus::Archived && static::currentUser()?->isAdmin() === true)
                    ->action(fn (ContentModel $record) => static::transition($record, ContentStatus::Archived)),
            ])->label('Statut')->icon(Heroicon::OutlinedEllipsisVertical),
        ];
    }

    protected static function transition(ContentModel $record, ContentStatus $to): void
    {
        static::run(fn () => app(ChangeContentStatus::class)->handle($record, $to, static::currentUserOrFail()), 'Statut : '.$to->label());
    }

    /**
     * Exécute une Action métier et traduit ses refus en notification.
     */
    protected static function run(callable $callback, string $success): void
    {
        try {
            $callback();
            Notification::make()->success()->title($success)->send();
        } catch (ContentTransitionException $e) {
            Notification::make()->danger()->title('Action impossible')->body($e->getMessage())->persistent()->send();
        } catch (AuthorizationException $e) {
            Notification::make()->danger()->title('Action non autorisée')->body($e->getMessage())->send();
        }
    }

    /**
     * @return list<string>
     */
    protected static function pending(ContentModel $record): array
    {
        return $record->pendingValidationFields();
    }

    protected static function statusOf(ContentModel $record): ContentStatus
    {
        return $record->status;
    }

    protected static function publicUrlOf(ContentModel $record): string
    {
        return $record->publicUrl();
    }

    protected static function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    protected static function currentUserOrFail(): User
    {
        return static::currentUser() ?? throw new AuthorizationException;
    }

    protected static function slugFrom(?string $title): string
    {
        return Str::slug((string) $title);
    }
}
