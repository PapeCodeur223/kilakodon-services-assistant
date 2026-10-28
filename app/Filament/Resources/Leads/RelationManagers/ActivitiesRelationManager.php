<?php

namespace App\Filament\Resources\Leads\RelationManagers;

use App\Domain\Crm\Actions\LogActivity;
use App\Domain\Crm\Enums\ActivityType;
use App\Domain\Crm\Models\Activity;
use App\Domain\Crm\Models\Lead;
use App\Filament\Resources\Leads\LeadActions;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Journal d'activités d'un lead (CRM-04) : interne, jamais visible du prospect.
 */
class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Activités';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        $tz = config('kh.display_timezone');

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('type')->label('Type')->badge()
                    ->formatStateUsing(fn (ActivityType $state): string => $state->label())
                    ->color(fn (ActivityType $state): string => $state === ActivityType::System ? 'gray' : 'primary'),
                TextColumn::make('body')->label('Contenu')->wrap()->limit(300),
                TextColumn::make('due_at')->label('Échéance')->dateTime('d/m/Y H:i', timezone: $tz)->placeholder('—')
                    ->color(fn (Activity $record): ?string => $record->isOpenTask() && $record->due_at?->isPast() ? 'danger' : null)
                    ->description(fn (Activity $record): ?string => $record->type === ActivityType::Task ? ($record->done_at !== null ? 'Faite' : 'À faire') : null),
                TextColumn::make('user.name')->label('Par')->placeholder('Plateforme'),
                TextColumn::make('created_at')->label('Le')->dateTime('d/m/Y H:i', timezone: $tz),
            ])
            ->filters([
                SelectFilter::make('type')->label('Type')->options(ActivityType::options()),
            ])
            ->headerActions([
                LeadActions::logActivity(fn (): Lead => $this->leadOwner())->icon(Heroicon::OutlinedPlus),
            ])
            ->recordActions([
                Action::make('complete')
                    ->label('Marquer comme faite')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (Activity $record): bool => $record->isOpenTask())
                    ->action(fn (Activity $record) => LeadActions::run(fn () => app(LogActivity::class)->complete($record), 'Tâche terminée')),
            ]);
    }

    private function leadOwner(): Lead
    {
        $owner = $this->getOwnerRecord();

        return $owner instanceof Lead ? $owner : throw new \LogicException('Propriétaire inattendu.');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }
}
