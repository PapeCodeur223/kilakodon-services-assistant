<?php

namespace App\Filament\Widgets;

use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Crm\Enums\LeadType;
use App\Domain\Crm\Models\Lead;
use App\Filament\Resources\Leads\LeadResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * « Leads à traiter » (UX §5.8, CRM-02) : les plus anciens d'abord.
 */
class LeadsToHandle extends TableWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Leads à traiter';

    public static function canView(): bool
    {
        return CrmOverview::canView();
    }

    public function table(Table $table): Table
    {
        $tz = config('kh.display_timezone');

        return $table
            ->query(fn () => Lead::query()->toHandle()->with(['contact.company', 'owner'])->oldest())
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Rien à traiter')
            ->emptyStateDescription('Chaque lead actif a une prochaine action planifiée.')
            ->recordUrl(fn (Lead $record): string => LeadResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('contact.full_name')->label('Contact')
                    ->description(fn (Lead $record): ?string => $record->contact->company?->name),
                TextColumn::make('type')->label('Origine')->badge()->color('gray')->formatStateUsing(fn (LeadType $state): string => $state->label()),
                TextColumn::make('stage')->label('Étape')->badge()
                    ->formatStateUsing(fn (LeadStage $state): string => $state->label())
                    ->color(fn (LeadStage $state): string => $state->color()),
                TextColumn::make('next_action')->label('Prochaine action')->placeholder('Aucune')->color('danger'),
                TextColumn::make('created_at')->label('Reçu')->since($tz),
                TextColumn::make('owner.name')->label('Responsable')->placeholder('Non attribué'),
            ]);
    }
}
