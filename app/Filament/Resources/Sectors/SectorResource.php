<?php

namespace App\Filament\Resources\Sectors;

use App\Domain\Content\Models\Sector;
use App\Filament\Concerns\ManagesContent;
use App\Filament\RelationManagers\RevisionsRelationManager;
use App\Filament\Resources\Sectors\Pages\CreateSector;
use App\Filament\Resources\Sectors\Pages\EditSector;
use App\Filament\Resources\Sectors\Pages\ListSectors;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SectorResource extends Resource
{
    use ManagesContent;

    protected static ?string $model = Sector::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Contenus';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Secteurs';

    protected static ?string $modelLabel = 'secteur';

    protected static ?string $pluralModelLabel = 'secteurs';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        $items = fn (string $name, string $label) => Repeater::make($name)->label($label)->required()->minItems(1)->maxItems(8)->columns(2)
            ->schema([
                TextInput::make('title')->label('Titre')->required()->maxLength(160),
                TextInput::make('text')->label('Précision')->maxLength(255),
            ]);

        return $schema->columns(1)->components([
            Section::make('Présentation')->columns(2)->schema([
                TextInput::make('name')->label('Nom')->required()->maxLength(120),
                TextInput::make('code')->label('Code du diagnostic')->required()->maxLength(48)->alphaDash()->unique(ignoreRecord: true)->helperText('Ex. retail — utilisé par ?secteur= du diagnostic.'),
                TextInput::make('headline')->label('Titre de la page')->required()->maxLength(200)->columnSpanFull()->helperText('Un seul fragment entre *astérisques* pour l’accent rouge.'),
                Textarea::make('intro')->label('Introduction')->required()->rows(3)->columnSpanFull(),
                TextInput::make('icon')->label('Icône (Lucide)')->maxLength(48),
                TextInput::make('demo_slug')->label('Démo associée')->maxLength(60)->helperText('boutique, gestion-scolaire, boulangerie'),
            ]),
            Section::make('Contenu')->schema([
                $items('pain_points', 'Vos défis'),
                $items('outcomes', 'Ce que ça change'),
                Select::make('services')->label('Solutions recommandées')->relationship('services', 'title')->multiple()->preload(),
            ]),
            static::seoSection(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('code')->label('Code'),
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
            'index' => ListSectors::route('/'),
            'create' => CreateSector::route('/create'),
            'edit' => EditSector::route('/{record}/edit'),
        ];
    }
}
