<?php

namespace App\Filament\Resources\Articles;

use App\Domain\Content\Enums\ArticleCategory;
use App\Domain\Content\Models\Article;
use App\Filament\Concerns\ManagesContent;
use App\Filament\RelationManagers\RevisionsRelationManager;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ArticleResource extends Resource
{
    use ManagesContent;

    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Contenus';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Articles';

    protected static ?string $modelLabel = 'article';

    protected static ?string $pluralModelLabel = 'articles';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Article')->columns(2)->schema([
                TextInput::make('title')->label('Titre')->required()->maxLength(160)->columnSpanFull()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', static::slugFrom($state)) : null),
                Select::make('category')->label('Catégorie')->options(ArticleCategory::options())->required(),
                Select::make('author_id')->label('Auteur')->relationship('author', 'name')->helperText('Vide : « L’équipe Kilakodon ».'),
                Textarea::make('excerpt')->label('Chapeau')->required()->maxLength(300)->rows(2)->columnSpanFull(),
                MarkdownEditor::make('body')->label('Texte')->required()->columnSpanFull()
                    ->helperText('Aucun chiffre sans source citée, aucune donnée client (CONTENT §9).'),
            ]),
            static::seoSection(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('title')->label('Titre')->searchable()->limit(60),
                TextColumn::make('category')->label('Catégorie')->formatStateUsing(fn (ArticleCategory $state): string => $state->label()),
                TextColumn::make('reading_minutes')->label('Lecture')->suffix(' min'),
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
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
