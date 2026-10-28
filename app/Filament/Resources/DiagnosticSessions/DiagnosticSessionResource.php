<?php

namespace App\Filament\Resources\DiagnosticSessions;

use App\Domain\Diagnostic\Enums\SessionStatus;
use App\Domain\Diagnostic\Models\DiagnosticAnswer;
use App\Domain\Diagnostic\Models\DiagnosticSession;
use App\Filament\Resources\DiagnosticSessions\Pages\ListDiagnosticSessions;
use App\Filament\Resources\DiagnosticSessions\Pages\ViewDiagnosticSession;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
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
 * Sessions de diagnostic (lecture seule) : réponses brutes et restitution calculée.
 */
class DiagnosticSessionResource extends Resource
{
    protected static ?string $model = DiagnosticSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Diagnostic';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Sessions';

    protected static ?string $modelLabel = 'session de diagnostic';

    protected static ?string $pluralModelLabel = 'sessions de diagnostic';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['template', 'result', 'answers', 'brief']);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Section::make('Session')->columnSpanFull()->columns(4)->schema([
                TextEntry::make('status')->label('Statut')->badge()->formatStateUsing(fn (SessionStatus $state): string => $state->label()),
                TextEntry::make('template.version')->label('Questionnaire')->prefix('v'),
                TextEntry::make('entry')->label('Entrée')->placeholder('—'),
                TextEntry::make('brief.reference')->label('Brief')->placeholder('—'),
                TextEntry::make('started_at')->label('Commencée le')->dateTime('d/m/Y H:i', timezone: config('kh.display_timezone')),
                TextEntry::make('completed_at')->label('Terminée le')->dateTime('d/m/Y H:i', timezone: config('kh.display_timezone'))->placeholder('—'),
                TextEntry::make('expires_at')->label('Expire le')->dateTime('d/m/Y', timezone: config('kh.display_timezone')),
                TextEntry::make('current_step')->label('Étape'),
            ]),
            Section::make('Restitution')->columnSpanFull()->columns(2)->visible(fn (DiagnosticSession $record): bool => $record->result !== null)->schema([
                TextEntry::make('result.maturity_level')->label('Maturité')
                    ->formatStateUsing(fn (DiagnosticSession $record): string => $record->result?->toOutcome()->maturityLabel().' (niveau '.$record->result?->maturity_level.', score '.$record->result?->maturity_score.')'),
                TextEntry::make('modules')->label('Modules recommandés')
                    ->getStateUsing(fn (DiagnosticSession $record): string => collect($record->result->recommendations ?? [])
                        ->map(fn (array $r): string => $r['label'].' ('.$r['weight'].')')->implode(' · ') ?: 'Aucun (échange à prévoir)'),
                TextEntry::make('pains')->label('Priorités')
                    ->getStateUsing(fn (DiagnosticSession $record): string => collect($record->result->pains ?? [])->pluck('label')->implode(' · ') ?: '—'),
                TextEntry::make('clarifications')->label('À clarifier')
                    ->getStateUsing(fn (DiagnosticSession $record): string => collect($record->result->clarifications ?? [])->pluck('label')->implode(' · ') ?: '—'),
            ]),
            Section::make('Réponses')->columnSpanFull()->schema([
                RepeatableEntry::make('answers')->hiddenLabel()->columns(2)->schema([
                    TextEntry::make('question_key')->label('Question')->fontFamily('mono'),
                    TextEntry::make('value')->label('Réponse')->getStateUsing(fn (DiagnosticAnswer $record): string => is_array($record->value) ? implode(', ', $record->value) : (string) $record->value),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('started_at', 'desc')
            ->columns([
                TextColumn::make('started_at')->label('Commencée le')->dateTime('d/m/Y H:i', timezone: config('kh.display_timezone'))->sortable(),
                TextColumn::make('status')->label('Statut')->badge()->formatStateUsing(fn (SessionStatus $state): string => $state->label())
                    ->color(fn (SessionStatus $state): string => match ($state) {
                        SessionStatus::Converted => 'success',
                        SessionStatus::Completed => 'info',
                        SessionStatus::Expired => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('entry')->label('Entrée')->placeholder('—'),
                TextColumn::make('current_step')->label('Étape'),
                TextColumn::make('result.maturity_level')->label('Maturité')->placeholder('—'),
                TextColumn::make('brief.reference')->label('Brief')->placeholder('—'),
                TextColumn::make('template.version')->label('Version')->prefix('v'),
            ])
            ->filters([SelectFilter::make('status')->label('Statut')->options(SessionStatus::options())])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiagnosticSessions::route('/'),
            'view' => ViewDiagnosticSession::route('/{record}'),
        ];
    }
}
