<?php

namespace App\Filament\Resources\Leads\RelationManagers;

use App\Domain\Crm\Enums\LeadStage;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Historique des étapes (CRM-02) : auteur, date, motif. Lecture seule.
 */
class StageHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'stageHistory';

    protected static ?string $title = 'Historique des étapes';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('from_stage')->label('De')->badge()
                    ->formatStateUsing(fn (?LeadStage $state): string => $state?->label() ?? '—')
                    ->color(fn (?LeadStage $state): string => $state?->color() ?? 'gray'),
                TextColumn::make('to_stage')->label('Vers')->badge()
                    ->formatStateUsing(fn (LeadStage $state): string => $state->label())
                    ->color(fn (LeadStage $state): string => $state->color()),
                TextColumn::make('reason')->label('Motif')->wrap()->placeholder('—'),
                TextColumn::make('user.name')->label('Par')->placeholder('Plateforme'),
                TextColumn::make('created_at')->label('Le')->dateTime('d/m/Y H:i', timezone: config('kh.display_timezone')),
            ]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }
}
