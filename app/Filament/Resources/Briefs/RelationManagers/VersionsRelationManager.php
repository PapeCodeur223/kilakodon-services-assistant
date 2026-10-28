<?php

namespace App\Filament\Resources\Briefs\RelationManagers;

use App\Domain\Brief\Enums\SummarySource;
use App\Domain\Brief\Enums\VersionAuthor;
use App\Domain\Brief\Models\BriefVersion;
use Filament\Actions\Action;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Versions figées d'un brief (BRF-02) : consultables telles quelles, jamais modifiables.
 */
class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Versions';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version')
            ->defaultSort('version', 'desc')
            ->columns([
                TextColumn::make('version')->label('Version')->prefix('v'),
                TextColumn::make('created_by')->label('Auteur')->formatStateUsing(fn (VersionAuthor $state): string => $state->label()),
                TextColumn::make('summary_source')->label('Résumé')->formatStateUsing(fn (SummarySource $state): string => $state->label()),
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i', timezone: config('kh.display_timezone')),
            ])
            ->recordActions([
                Action::make('show')
                    ->label('Voir')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->modalHeading(fn (BriefVersion $record): string => 'Version '.$record->version)
                    ->modalSubmitAction(false)
                    ->schema([
                        TextEntry::make('summary')->label('Résumé')->prose(),
                        TextEntry::make('prospect_comment')->label('Message du prospect')->placeholder('—'),
                        CodeEntry::make('declared')->label('Déclaré')
                            ->state(fn (BriefVersion $record): string => (string) json_encode($record->declared, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
                        CodeEntry::make('recommended')->label('Recommandé')
                            ->state(fn (BriefVersion $record): string => (string) json_encode($record->recommended, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
                    ]),
            ]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }
}
