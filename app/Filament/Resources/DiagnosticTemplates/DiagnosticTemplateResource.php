<?php

namespace App\Filament\Resources\DiagnosticTemplates;

use App\Domain\Diagnostic\Actions\ActivateDiagnosticTemplate;
use App\Domain\Diagnostic\Actions\DuplicateDiagnosticTemplate;
use App\Domain\Diagnostic\Enums\TemplateStatus;
use App\Domain\Diagnostic\Models\DiagnosticTemplate;
use App\Filament\Resources\DiagnosticTemplates\Pages\CreateDiagnosticTemplate;
use App\Filament\Resources\DiagnosticTemplates\Pages\EditDiagnosticTemplate;
use App\Filament\Resources\DiagnosticTemplates\Pages\ListDiagnosticTemplates;
use App\Filament\Resources\DiagnosticTemplates\Pages\ViewDiagnosticTemplate;
use App\Filament\Resources\DiagnosticTemplates\RelationManagers\QuestionsRelationManager;
use App\Filament\Resources\DiagnosticTemplates\RelationManagers\RulesRelationManager;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use UnitEnum;

/**
 * Éditeur des questionnaires (lot 2) : un brouillon est modifiable ; un template actif ou retiré
 * est en lecture seule et évolue par « Dupliquer en nouvelle version » (BACKEND_SCHEMA §4).
 */
class DiagnosticTemplateResource extends Resource
{
    protected static ?string $model = DiagnosticTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Diagnostic';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Questionnaires';

    protected static ?string $modelLabel = 'questionnaire';

    protected static ?string $pluralModelLabel = 'questionnaires';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nom')->required()->maxLength(120),
            TextInput::make('version')->label('Version')->integer()->minValue(1)->required()->disabledOn('edit')
                ->default(fn (): int => (int) DiagnosticTemplate::query()->max('version') + 1)
                ->unique(ignoreRecord: true),
            Textarea::make('notes')->label('Notes internes')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            TextEntry::make('name')->label('Nom'),
            TextEntry::make('version')->label('Version')->prefix('v'),
            TextEntry::make('status')->label('Statut')->badge()
                ->formatStateUsing(fn (TemplateStatus $state): string => $state->label())
                ->color(fn (TemplateStatus $state): string => self::statusColor($state)),
            TextEntry::make('activated_at')->label('Activé le')->dateTime('d/m/Y H:i', timezone: config('kh.display_timezone'))->placeholder('—'),
            TextEntry::make('notes')->label('Notes')->placeholder('—')->columnSpan(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('version', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->withCount(['questions', 'rules']))
            ->columns([
                TextColumn::make('version')->label('Version')->prefix('v')->sortable(),
                TextColumn::make('name')->label('Nom'),
                TextColumn::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (TemplateStatus $state): string => $state->label())
                    ->color(fn (TemplateStatus $state): string => self::statusColor($state)),
                TextColumn::make('questions_count')->label('Questions'),
                TextColumn::make('rules_count')->label('Règles'),
                TextColumn::make('activated_at')->label('Activé le')->dateTime('d/m/Y H:i', timezone: config('kh.display_timezone'))->placeholder('—'),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }

    /**
     * Activer un brouillon, dupliquer une version : uniquement via les Actions métier.
     *
     * @return list<Action>
     */
    public static function lifecycleActions(): array
    {
        return [
            Action::make('activate')
                ->label('Activer')
                ->icon(Heroicon::OutlinedPlay)
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Les nouveaux diagnostics utiliseront cette version ; la version active actuelle sera retirée. Une version activée ne peut plus être modifiée. Les sessions en cours gardent leur version.')
                ->visible(fn (DiagnosticTemplate $record): bool => auth()->user()?->can('activate', $record) === true)
                ->action(function (DiagnosticTemplate $record, Component $livewire): void {
                    try {
                        app(ActivateDiagnosticTemplate::class)->handle($record);
                        Notification::make()->success()->title("Version {$record->version} activée")->send();
                        $livewire->redirect(self::getUrl('view', ['record' => $record]));
                    } catch (ValidationException $e) {
                        Notification::make()->danger()->title('Activation impossible')
                            ->body(collect($e->errors())->flatten()->implode("\n"))
                            ->persistent()->send();
                    }
                }),
            Action::make('duplicate')
                ->label('Dupliquer en nouvelle version')
                ->icon(Heroicon::OutlinedDocumentDuplicate)
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (DiagnosticTemplate $record): bool => auth()->user()?->can('duplicate', $record) === true)
                ->action(function (DiagnosticTemplate $record, Component $livewire): void {
                    $copy = app(DuplicateDiagnosticTemplate::class)->handle($record);
                    Notification::make()->success()->title("Brouillon v{$copy->version} créé")->send();
                    $livewire->redirect(self::getUrl('edit', ['record' => $copy]));
                }),
        ];
    }

    public static function statusColor(TemplateStatus $status): string
    {
        return match ($status) {
            TemplateStatus::Active => 'success',
            TemplateStatus::Draft => 'warning',
            TemplateStatus::Retired => 'gray',
        };
    }

    public static function getRelations(): array
    {
        return [QuestionsRelationManager::class, RulesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiagnosticTemplates::route('/'),
            'create' => CreateDiagnosticTemplate::route('/create'),
            'view' => ViewDiagnosticTemplate::route('/{record}'),
            'edit' => EditDiagnosticTemplate::route('/{record}/edit'),
        ];
    }
}
