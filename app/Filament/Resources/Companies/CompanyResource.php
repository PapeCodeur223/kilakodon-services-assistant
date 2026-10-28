<?php

namespace App\Filament\Resources\Companies;

use App\Domain\Crm\Models\Company;
use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Filament\Resources\Leads\QualificationOptions;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Entreprises des contacts (CRM-01) : secteur, taille et nombre de sites repris du diagnostic.
 */
class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?int $navigationSort = 16;

    protected static ?string $navigationLabel = 'Entreprises';

    protected static ?string $modelLabel = 'entreprise';

    protected static ?string $pluralModelLabel = 'entreprises';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Nom')->required()->maxLength(160),
            TextInput::make('city')->label('Ville')->maxLength(80),
            Select::make('sector_code')->label('Secteur')->options(fn (): array => QualificationOptions::for('sector')),
            Select::make('size_band')->label('Effectif')->options(fn (): array => QualificationOptions::for('team_size')),
            Select::make('sites_band')->label('Sites')->options(fn (): array => QualificationOptions::for('sites')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(fn ($query) => $query->withCount('contacts'))
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable(),
                TextColumn::make('sector_code')->label('Secteur')->formatStateUsing(fn (?string $state): ?string => QualificationOptions::label('sector', $state))->placeholder('—'),
                TextColumn::make('size_band')->label('Effectif')->formatStateUsing(fn (?string $state): ?string => QualificationOptions::label('team_size', $state))->placeholder('—'),
                TextColumn::make('city')->label('Ville')->placeholder('—'),
                TextColumn::make('contacts_count')->label('Contacts'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanies::route('/'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }
}
