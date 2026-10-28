<?php

namespace App\Filament\Resources\Faqs;

use App\Domain\Content\Enums\FaqCategory;
use App\Domain\Content\Models\Faq;
use App\Filament\Concerns\ManagesContent;
use App\Filament\RelationManagers\RevisionsRelationManager;
use App\Filament\Resources\Faqs\Pages\CreateFaq;
use App\Filament\Resources\Faqs\Pages\EditFaq;
use App\Filament\Resources\Faqs\Pages\ListFaqs;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class FaqResource extends Resource
{
    use ManagesContent;

    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Contenus';

    protected static ?int $navigationSort = 50;

    protected static ?string $navigationLabel = 'FAQ';

    protected static ?string $modelLabel = 'question';

    protected static ?string $pluralModelLabel = 'questions';

    protected static ?string $recordTitleAttribute = 'question';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Question')->columns(2)->schema([
                TextInput::make('question')->label('Question')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('answer')->label('Réponse')->required()->rows(4)->columnSpanFull(),
                Select::make('category')->label('Catégorie')->options(FaqCategory::options())->required(),
                TextInput::make('sort')->label('Ordre')->integer()->minValue(0)->default(0),
                Toggle::make('on_home')->label('Afficher sur l’accueil (6 au maximum)'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('question')->label('Question')->searchable()->limit(70),
                TextColumn::make('category')->label('Catégorie')->formatStateUsing(fn (FaqCategory $state): string => $state->label()),
                IconColumn::make('on_home')->label('Accueil')->boolean(),
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
            'index' => ListFaqs::route('/'),
            'create' => CreateFaq::route('/create'),
            'edit' => EditFaq::route('/{record}/edit'),
        ];
    }
}
