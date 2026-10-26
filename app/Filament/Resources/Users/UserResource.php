<?php

namespace App\Filament\Resources\Users;

use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\User;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

/**
 * Utilisateurs internes — administrateurs uniquement (UserPolicy).
 * Pas de suppression : un compte se désactive (ADM-02).
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Réglages';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'utilisateur';

    protected static ?string $pluralModelLabel = 'utilisateurs';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Compte')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->maxLength(190)
                            ->unique(ignoreRecord: true),
                        Select::make('role')
                            ->label('Rôle')
                            ->options(UserRole::options())
                            ->enum(UserRole::class)
                            ->required()
                            ->native(false)
                            ->disabled(fn (?User $record): bool => $record !== null && ! static::currentUserCan('changeRole', $record))
                            ->helperText(fn (?User $record): ?string => $record !== null && $record->is(auth()->user()) ? 'Vous ne pouvez pas modifier votre propre rôle.' : null),
                        Toggle::make('is_active')
                            ->label('Compte actif')
                            ->default(true)
                            ->disabled(fn (?User $record): bool => $record !== null && $record->is(auth()->user())),
                    ]),
                Section::make('Mot de passe')
                    ->description('12 caractères minimum, avec lettres et chiffres.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('password')
                            ->label('Mot de passe')
                            ->password()
                            ->revealable()
                            ->rule(Password::defaults())
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->confirmed()
                            ->autocomplete('new-password'),
                        TextInput::make('password_confirmation')
                            ->label('Confirmation')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(false)
                            ->autocomplete('new-password'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Rôle')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->label())
                    ->color(fn (UserRole $state): string => match ($state) {
                        UserRole::Admin => 'primary',
                        UserRole::Sales => 'info',
                        UserRole::Editor => 'gray',
                    }),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                IconColumn::make('has_mfa')
                    ->label('MFA')
                    ->boolean()
                    ->state(fn (User $record): bool => filled($record->app_authentication_secret)),
                TextColumn::make('last_login_at')
                    ->label('Dernière connexion')
                    ->dateTime('d/m/Y H:i', timezone: config('kh.display_timezone'))
                    ->placeholder('Jamais')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Rôle')
                    ->options(UserRole::options()),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    protected static function currentUserCan(string $ability, User $record): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can($ability, $record);
    }
}
