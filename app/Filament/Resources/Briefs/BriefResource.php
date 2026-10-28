<?php

namespace App\Filament\Resources\Briefs;

use App\Domain\Brief\Actions\RevokeBriefAccess;
use App\Domain\Brief\Enums\BriefStatus;
use App\Domain\Brief\Models\Brief;
use App\Filament\Resources\Briefs\Pages\ListBriefs;
use App\Filament\Resources\Briefs\Pages\ViewBrief;
use App\Filament\Resources\Briefs\RelationManagers\VersionsRelationManager;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Briefs (BRF-01..04) : même distinction que côté prospect (déclaré / recommandé / résumé),
 * versions figées consultables telles quelles. Les notes internes arrivent avec les activités (lot 3).
 */
class BriefResource extends Resource
{
    protected static ?string $model = Brief::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Briefs';

    protected static ?string $modelLabel = 'brief';

    protected static ?string $pluralModelLabel = 'briefs';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['contact', 'lead', 'latestVersion']);
    }

    public static function infolist(Schema $schema): Schema
    {
        $tz = config('kh.display_timezone');

        return $schema->columns(2)->components([
            Section::make('Brief')->columnSpanFull()->columns(4)->schema([
                TextEntry::make('reference')->label('Référence')->copyable(),
                TextEntry::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (BriefStatus $state): string => $state->label())
                    ->color(fn (BriefStatus $state): string => $state === BriefStatus::Validated ? 'success' : 'warning'),
                TextEntry::make('current_version')->label('Version courante')->prefix('v'),
                TextEntry::make('validated_at')->label('Validé le')->dateTime('d/m/Y H:i', timezone: $tz)->placeholder('—'),
                TextEntry::make('contact.full_name')->label('Contact'),
                TextEntry::make('contact.phone_e164')->label('Téléphone')->copyable(),
                TextEntry::make('contact.email')->label('E-mail')->placeholder('—')->copyable(),
                TextEntry::make('access')->label('Lien privé')->getStateUsing(fn (Brief $record): string => match (true) {
                    $record->isRevoked() => 'Révoqué',
                    $record->isExpired() => 'Expiré',
                    default => 'Valable jusqu\'au '.$record->access_expires_at->timezone(config('kh.display_timezone'))->format('d/m/Y'),
                }),
            ]),
            Section::make('Déclaré par le prospect')->columnSpanFull()->schema([
                TextEntry::make('declared')->hiddenLabel()->getStateUsing(fn (Brief $record): string => collect($record->latestVersion->declared['answers'] ?? [])
                    ->map(fn (array $a): string => $a['question'].' — '.$a['answer'])->implode("\n"))->listWithLineBreaks()->extraAttributes(['class' => 'whitespace-pre-line']),
                TextEntry::make('context_note')->label('Précisions')->getStateUsing(fn (Brief $record): ?string => $record->latestVersion->declared['context_note'] ?? null)->placeholder('—'),
                TextEntry::make('comment')->label('Message du prospect')->getStateUsing(fn (Brief $record): ?string => $record->latestVersion?->prospect_comment)->placeholder('—'),
            ]),
            Section::make('Recommandé par Kilakodon (règles)')->columnSpanFull()->columns(2)->schema([
                TextEntry::make('maturity')->label('Maturité')->getStateUsing(fn (Brief $record): string => ($record->latestVersion->recommended['maturity']['label'] ?? '—').' (niveau '.($record->latestVersion->recommended['maturity']['level'] ?? '?').')'),
                TextEntry::make('modules')->label('Modules')->getStateUsing(fn (Brief $record): string => collect($record->latestVersion->recommended['recommendations'] ?? [])->pluck('label')->implode(' · ') ?: 'Aucun — échange à prévoir'),
                TextEntry::make('pains')->label('Priorités')->getStateUsing(fn (Brief $record): string => collect($record->latestVersion->recommended['pains'] ?? [])->pluck('label')->implode(' · ') ?: '—'),
                TextEntry::make('clarifications')->label('À clarifier')->getStateUsing(fn (Brief $record): string => collect($record->latestVersion->recommended['clarifications'] ?? [])->pluck('label')->implode(' · ') ?: '—'),
            ]),
            Section::make('Résumé')->columnSpanFull()->schema([
                TextEntry::make('summary_source')->label('Source')->getStateUsing(fn (Brief $record): string => $record->latestVersion?->summary_source->label() ?? '—'),
                TextEntry::make('summary')->hiddenLabel()->getStateUsing(fn (Brief $record): string => $record->latestVersion->summary ?? '')->prose(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')->label('Référence')->searchable()->fontFamily('mono'),
                TextColumn::make('created_at')->label('Créé le')->dateTime('d/m/Y H:i', timezone: config('kh.display_timezone'))->sortable(),
                TextColumn::make('contact.full_name')->label('Contact')->searchable(),
                TextColumn::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (BriefStatus $state): string => $state->label())
                    ->color(fn (BriefStatus $state): string => $state === BriefStatus::Validated ? 'success' : 'warning'),
                TextColumn::make('current_version')->label('Version')->prefix('v'),
                TextColumn::make('access_expires_at')->label('Lien jusqu\'au')->date('d/m/Y', timezone: config('kh.display_timezone')),
            ])
            ->filters([SelectFilter::make('status')->label('Statut')->options(BriefStatus::options())])
            ->recordActions([ViewAction::make()]);
    }

    public static function revokeAction(): Action
    {
        return Action::make('revoke')
            ->label('Révoquer le lien privé')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('Le prospect ne pourra plus ouvrir son brief avec ce lien. Le brief reste consultable ici.')
            ->visible(fn (Brief $record): bool => auth()->user()?->can('revoke', $record) === true)
            ->action(function (Brief $record): void {
                app(RevokeBriefAccess::class)->handle($record);
                Notification::make()->success()->title('Lien révoqué')->send();
            });
    }

    public static function getRelations(): array
    {
        return [VersionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBriefs::route('/'),
            'view' => ViewBrief::route('/{record}'),
        ];
    }
}
