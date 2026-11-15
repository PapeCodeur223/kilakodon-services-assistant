<?php

namespace App\Filament\Widgets;

use App\Domain\Crm\Enums\AppointmentStatus;
use App\Domain\Crm\Models\Appointment;
use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * « RDV demandés » (UX §5.8) : à confirmer par un humain (RDV-01).
 */
class RequestedAppointments extends TableWidget
{
    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Rendez-vous à confirmer';

    public static function canView(): bool
    {
        return CrmOverview::canView();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Appointment::query()->where('status', AppointmentStatus::Requested)->with('lead.contact')->oldest())
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Aucune demande en attente')
            ->columns([
                TextColumn::make('lead.contact.full_name')->label('Contact'),
                TextColumn::make('topic')->label('Motif')->limit(40),
                TextColumn::make('slots')->label('Créneaux souhaités')->listWithLineBreaks()
                    ->getStateUsing(fn (Appointment $record): array => $record->slotLabels()),
                TextColumn::make('created_at')->label('Demandé')->since(config('kh.display_timezone')),
            ])
            ->recordActions([AppointmentResource::confirmAction()]);
    }
}
