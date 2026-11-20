<?php

namespace App\Filament\Resources\KnowledgeDocuments;

use App\Domain\Assistant\Actions\ReviewKnowledgeDocument;
use App\Domain\Assistant\Enums\KnowledgeSourceType;
use App\Domain\Assistant\Enums\KnowledgeStatus;
use App\Domain\Assistant\Models\KnowledgeDocument;
use App\Domain\Platform\Models\User;
use App\Filament\Resources\KnowledgeDocuments\Pages\CreateKnowledgeDocument;
use App\Filament\Resources\KnowledgeDocuments\Pages\EditKnowledgeDocument;
use App\Filament\Resources\KnowledgeDocuments\Pages\ListKnowledgeDocuments;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

/**
 * « Base de connaissances » (TRD §7.1) : documents issus des contenus publiés (reconstruits
 * automatiquement) et documents manuels. L'assistant n'interroge que les documents approuvés par un admin.
 */
class KnowledgeDocumentResource extends Resource
{
    protected static ?string $model = KnowledgeDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Assistant';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Base de connaissances';

    protected static ?string $modelLabel = 'document';

    protected static ?string $pluralModelLabel = 'documents';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationBadge(): ?string
    {
        $count = KnowledgeDocument::query()->where('status', KnowledgeStatus::Draft)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Brouillons à approuver';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('chunks')->with('approver');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Document manuel')
                ->description('Réservé aux informations publiques absentes des pages du site. Toute modification remet le document en brouillon : il faudra le faire approuver par un administrateur.')
                ->columns(2)
                ->schema([
                    TextInput::make('title')->label('Titre')->required()->maxLength(255)->columnSpanFull(),
                    MarkdownEditor::make('body')->label('Texte (Markdown, sections « ## Titre »)')->required()->columnSpanFull()
                        ->toolbarButtons(['bold', 'bulletList', 'heading', 'link', 'undo', 'redo']),
                    TextInput::make('url')->label('Page citée comme source')->url()->maxLength(255)
                        ->startsWith([rtrim(url('/'), '/')])->validationMessages(['starts_with' => 'Indiquez une page de ce site.'])
                        ->helperText('Lien interne au site. Vide : la page d\'accueil.')->placeholder(url('/')),
                    DatePicker::make('review_at')->label('À relire le'),
                    DatePicker::make('expires_at')->label('Expire le')->helperText('Après cette date, l\'assistant ne l\'utilise plus.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $tz = config('kh.display_timezone');

        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Titre')->searchable()->limit(60)->wrap(),
                TextColumn::make('source_type')->label('Source')->badge()->color('gray')
                    ->formatStateUsing(fn (KnowledgeSourceType $state): string => $state->label()),
                TextColumn::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (KnowledgeStatus $state): string => $state->label())
                    ->color(fn (KnowledgeStatus $state): string => match ($state) {
                        KnowledgeStatus::Approved => 'success',
                        KnowledgeStatus::Draft => 'warning',
                        KnowledgeStatus::Retired => 'gray',
                    }),
                TextColumn::make('version')->label('Version')->prefix('v'),
                TextColumn::make('chunks_count')->label('Fragments'),
                TextColumn::make('approver.name')->label('Approuvé par')->placeholder('—'),
                TextColumn::make('updated_at')->label('Modifié le')->dateTime('d/m/Y H:i', timezone: $tz)->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(KnowledgeStatus::options()),
                SelectFilter::make('source_type')->label('Source')->options(KnowledgeSourceType::options()),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approuver')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('L\'assistant pourra citer ce document dans ses réponses. Vérifiez qu\'il ne contient que des informations publiques et validées.')
                    ->visible(fn (KnowledgeDocument $record): bool => auth()->user()?->can('approve', $record) === true)
                    ->action(function (KnowledgeDocument $record): void {
                        /** @var User $user */
                        $user = auth()->user();
                        app(ReviewKnowledgeDocument::class)->approve($record, $user);
                        Notification::make()->title('Document approuvé')->success()->send();
                    }),
                Action::make('retire')
                    ->label('Retirer')
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('L\'assistant n\'utilisera plus ce document.')
                    ->visible(fn (KnowledgeDocument $record): bool => auth()->user()?->can('retire', $record) === true)
                    ->action(function (KnowledgeDocument $record): void {
                        /** @var User $user */
                        $user = auth()->user();
                        app(ReviewKnowledgeDocument::class)->retire($record, $user);
                        Notification::make()->title('Document retiré')->success()->send();
                    }),
                EditAction::make(),
            ])
            // Mise en production (lot 7) : approuver d'un coup les documents générés depuis les contenus publiés.
            ->toolbarActions([
                BulkAction::make('approveSelected')
                    ->label('Approuver la sélection')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('L\'assistant pourra citer ces documents. Ne sélectionnez que des documents issus de contenus publics et validés.')
                    ->visible(fn (): bool => auth()->user()?->can('approveAny', KnowledgeDocument::class) === true)
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        /** @var User $user */
                        $user = auth()->user();
                        $approved = 0;
                        foreach ($records as $record) {
                            if ($record instanceof KnowledgeDocument && $record->status === KnowledgeStatus::Draft && $user->can('approve', $record)) {
                                app(ReviewKnowledgeDocument::class)->approve($record, $user);
                                $approved++;
                            }
                        }
                        Notification::make()->title($approved.' document(s) approuvé(s)')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKnowledgeDocuments::route('/'),
            'create' => CreateKnowledgeDocument::route('/create'),
            'edit' => EditKnowledgeDocument::route('/{record}/edit'),
        ];
    }
}
