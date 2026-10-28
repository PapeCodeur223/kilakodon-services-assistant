<?php

namespace App\Filament\Resources\CaseStudies;

use App\Domain\Content\Actions\AttestCaseStudyAuthorization;
use App\Domain\Content\Enums\AuthorizationStatus;
use App\Domain\Content\Enums\ClientDisplay;
use App\Domain\Content\Exceptions\ContentTransitionException;
use App\Domain\Content\Models\CaseStudy;
use App\Filament\Concerns\ManagesContent;
use App\Filament\RelationManagers\RevisionsRelationManager;
use App\Filament\Resources\CaseStudies\Pages\CreateCaseStudy;
use App\Filament\Resources\CaseStudies\Pages\EditCaseStudy;
use App\Filament\Resources\CaseStudies\Pages\ListCaseStudies;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use UnitEnum;

/**
 * Réalisations : publication bloquée sans autorisation client attestée par un admin (WEB-03).
 */
class CaseStudyResource extends Resource
{
    use ManagesContent;

    protected static ?string $model = CaseStudy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static string|UnitEnum|null $navigationGroup = 'Contenus';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Réalisations';

    protected static ?string $modelLabel = 'réalisation';

    protected static ?string $pluralModelLabel = 'réalisations';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Client')->columns(2)->schema([
                TextInput::make('title')->label('Titre')->required()->maxLength(160)->columnSpanFull(),
                Select::make('client_display')->label('Affichage du client')->options(ClientDisplay::options())->required()->live()->default(ClientDisplay::Anonymized->value),
                TextInput::make('client_name')->label('Nom du client')->maxLength(160)
                    ->required(fn (Get $get): bool => $get('client_display') === ClientDisplay::Named->value)
                    ->visible(fn (Get $get): bool => $get('client_display') === ClientDisplay::Named->value),
                TextInput::make('client_descriptor')->label('Descripteur')->required()->maxLength(160)->helperText('Ex. « Chaîne de 3 boutiques, Bamako »'),
                Select::make('sector_id')->label('Secteur')->relationship('sector', 'name'),
                Select::make('services')->label('Solutions')->relationship('services', 'title')->multiple()->preload(),
            ]),
            Section::make('Récit')->schema([
                MarkdownEditor::make('context')->label('Contexte')->required(),
                MarkdownEditor::make('challenge')->label('Défi')->required(),
                MarkdownEditor::make('intervention')->label('Intervention')->required(),
                TagsInput::make('deliverables')->label('Livrables')->required(),
            ]),
            Section::make('Résultats et témoignage')
                ->description('Les résultats ne s’affichent que s’ils sont vérifiés ; la citation, que si l’autorisation est accordée.')
                ->schema([
                    MarkdownEditor::make('results')->label('Résultats'),
                    Toggle::make('results_verified')->label('Résultats vérifiés (preuve conservée)'),
                    Textarea::make('quote')->label('Citation')->rows(3),
                    TextInput::make('quote_author')->label('Auteur de la citation')->maxLength(255),
                ]),
            static::seoSection(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('title')->label('Titre')->searchable(),
                TextColumn::make('client_descriptor')->label('Client'),
                TextColumn::make('authorization_status')->label('Autorisation')->badge()
                    ->formatStateUsing(fn (AuthorizationStatus $state): string => $state->label())
                    ->color(fn (AuthorizationStatus $state): string => $state === AuthorizationStatus::Granted ? 'success' : 'warning'),
                static::statusColumn(),
            ])
            ->filters([static::statusFilter()])
            ->recordActions([EditAction::make(), static::attestAction(), ...static::lifecycleActions()]);
    }

    public static function attestAction(): Action
    {
        return Action::make('attest')
            ->label('Autorisation client')
            ->icon(Heroicon::OutlinedShieldCheck)
            ->color('gray')
            ->visible(fn (): bool => static::currentUser()?->isAdmin() === true)
            ->fillForm(fn (CaseStudy $record): array => [
                'authorization_status' => $record->authorization_status->value,
                'authorization_note' => $record->authorization_note,
            ])
            ->schema([
                Select::make('authorization_status')->label('Statut')->options(AuthorizationStatus::options())->required(),
                Textarea::make('authorization_note')->label('Preuve (date, canal, interlocuteur)')->rows(3)
                    ->required(fn (Get $get): bool => $get('authorization_status') === AuthorizationStatus::Granted->value),
            ])
            ->action(function (CaseStudy $record, array $data): void {
                try {
                    app(AttestCaseStudyAuthorization::class)->handle(
                        $record,
                        AuthorizationStatus::from($data['authorization_status']),
                        $data['authorization_note'] ?? null,
                        static::currentUserOrFail(),
                    );
                    Notification::make()->success()->title('Autorisation enregistrée')->send();
                } catch (ContentTransitionException|AuthorizationException $e) {
                    Notification::make()->danger()->title('Action impossible')->body($e->getMessage())->send();
                }
            });
    }

    public static function getRelations(): array
    {
        return [RevisionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCaseStudies::route('/'),
            'create' => CreateCaseStudy::route('/create'),
            'edit' => EditCaseStudy::route('/{record}/edit'),
        ];
    }
}
