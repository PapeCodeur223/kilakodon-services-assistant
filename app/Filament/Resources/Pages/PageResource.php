<?php

namespace App\Filament\Resources\Pages;

use App\Domain\Content\Enums\PageTemplate;
use App\Domain\Content\Models\Page;
use App\Filament\Concerns\ManagesContent;
use App\Filament\RelationManagers\RevisionsRelationManager;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Pages éditoriales (méthode, à propos, légales). Les URL publiques sont fixes (TRD §5.1).
 */
class PageResource extends Resource
{
    use ManagesContent;

    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Contenus';

    protected static ?int $navigationSort = 60;

    protected static ?string $navigationLabel = 'Pages';

    protected static ?string $modelLabel = 'page';

    protected static ?string $pluralModelLabel = 'pages';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Page')->columns(2)->schema([
                TextInput::make('title')->label('Titre')->required()->maxLength(160)->columnSpanFull(),
                Select::make('template')->label('Gabarit')->options(PageTemplate::options())->required()->default(PageTemplate::Default->value),
                MarkdownEditor::make('body')->label('Contenu')->required()->columnSpanFull()
                    ->helperText('Pages légales : texte à valider par la direction et un conseil juridique avant publication.'),
            ]),
            static::seoSection(withSlug: false),
        ]);
    }

    /**
     * Les pages correspondent à des routes fixes : elles se modifient, ne se créent pas.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Titre')->searchable()->formatStateUsing(fn (string $state): string => str_replace('*', '', $state)),
                TextColumn::make('slug')->label('Adresse')->prefix('/'),
                static::statusColumn(),
                static::pendingColumn(),
            ])
            ->filters([static::statusFilter()])
            ->recordActions([EditAction::make(), ...static::lifecycleActions()]);
    }

    public static function getRelations(): array
    {
        return [RevisionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
