<?php

namespace App\Filament\Resources\Services;

use App\Domain\Content\Models\Service;
use App\Filament\Concerns\ManagesContent;
use App\Filament\RelationManagers\RevisionsRelationManager;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
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

class ServiceResource extends Resource
{
    use ManagesContent;

    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Contenus';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Solutions';

    protected static ?string $modelLabel = 'solution';

    protected static ?string $pluralModelLabel = 'solutions';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Présentation')->columns(2)->schema([
                TextInput::make('title')->label('Titre')->required()->maxLength(120)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', static::slugFrom($state)) : null),
                TextInput::make('icon')->label('Icône (Lucide)')->required()->maxLength(48)->helperText('Nom d’un fichier de resources/svg/lucide, ex. « package ».'),
                TextInput::make('tagline')->label('Accroche')->required()->maxLength(200)->columnSpanFull(),
                TextInput::make('module_code')->label('Module du diagnostic')->maxLength(48)->helperText('Ex. MOD_STOCK_POS'),
            ]),
            Section::make('Contenu')->schema([
                MarkdownEditor::make('problem')->label('Le problème')->required(),
                MarkdownEditor::make('audience')->label('Pour qui')->required(),
                Repeater::make('benefits')->label('Bénéfices')->required()->minItems(1)->maxItems(6)->columns(2)
                    ->schema([
                        TextInput::make('title')->label('Titre')->required()->maxLength(120),
                        TextInput::make('text')->label('Précision')->maxLength(200),
                    ]),
                Repeater::make('features')->label('Fonctionnalités')->required()->minItems(1)->maxItems(12)->columns(2)
                    ->schema([
                        TextInput::make('title')->label('Fonctionnalité')->required()->maxLength(160),
                        TextInput::make('text')->label('Précision')->maxLength(200),
                    ]),
                Repeater::make('process')->label('Étapes spécifiques (sinon : méthode générale)')->maxItems(6)->columns(2)
                    ->schema([
                        TextInput::make('title')->label('Étape')->required()->maxLength(120),
                        TextInput::make('text')->label('Description')->maxLength(255),
                    ]),
                MarkdownEditor::make('limits')->label('Ce que ce n’est pas'),
            ]),
            Section::make('Délai, prix et questions')->columns(2)->schema([
                TextInput::make('duration_hint')->label('Délai indicatif')->maxLength(120)->helperText('Laissez « [À VALIDER] » tant que la direction n’a pas confirmé.'),
                TextInput::make('price_hint')->label('Prix indicatif')->maxLength(120)->helperText('Vide = « Sur devis après diagnostic » (Q-004).'),
                Repeater::make('faq')->label('FAQ de la solution')->maxItems(6)->columnSpanFull()
                    ->schema([
                        TextInput::make('q')->label('Question')->required()->maxLength(255),
                        Textarea::make('a')->label('Réponse')->required()->rows(3),
                    ]),
            ]),
            static::seoSection(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('title')->label('Titre')->searchable()->sortable(),
                static::statusColumn(),
                static::pendingColumn(),
                TextColumn::make('updated_at')->label('Modifié le')->dateTime('d/m/Y H:i', timezone: config('kh.display_timezone'))->sortable(),
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
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }
}
