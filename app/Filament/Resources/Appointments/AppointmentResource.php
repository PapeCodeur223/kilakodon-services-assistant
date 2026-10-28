<?php

namespace App\Filament\Resources\Appointments;

use App\Domain\Crm\Actions\ConfirmAppointment;
use App\Domain\Crm\Enums\AppointmentChannel;
use App\Domain\Crm\Enums\AppointmentStatus;
use App\Domain\Crm\Models\Appointment;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Resources\Leads\LeadActions;
use App\Filament\Resources\Leads\LeadResource;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use UnitEnum;

/**
 * Rendez-vous (RDV-01) : une demande reste « demandée » tant qu'un humain ne l'a pas confirmée ici.
 */
class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Rendez-vous';

    protected static ?string $modelLabel = 'rendez-vous';

    protected static ?string $pluralModelLabel = 'rendez-vous';

    public static function getNavigationBadge(): ?string
    {
        $count = Appointment::query()->where('status', AppointmentStatus::Requested)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['lead.contact']);
    }

    public static function table(Table $table): Table
    {
        $tz = config('kh.display_timezone');

        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['lead.contact']))
            ->columns([
                TextColumn::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (AppointmentStatus $state): string => $state->label())
                    ->color(fn (AppointmentStatus $state): string => match ($state) {
                        AppointmentStatus::Requested => 'warning',
                        AppointmentStatus::Confirmed => 'success',
                        AppointmentStatus::Done => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('lead.contact.full_name')->label('Contact')
                    ->url(fn (Appointment $record): string => LeadResource::getUrl('view', ['record' => $record->lead_id])),
                TextColumn::make('topic')->label('Motif')->limit(40)->wrap(),
                TextColumn::make('channel')->label('Format')->formatStateUsing(fn (AppointmentChannel $state): string => $state->label()),
                TextColumn::make('slots')->label('Créneaux souhaités')->listWithLineBreaks()
                    ->getStateUsing(fn (Appointment $record): array => $record->slotLabels()),
                TextColumn::make('scheduled_at')->label('Programmé le')->dateTime('d/m/Y H:i', timezone: $tz)->placeholder('Non confirmé')->sortable(),
                TextColumn::make('created_at')->label('Demandé le')->dateTime('d/m/Y H:i', timezone: $tz)->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(AppointmentStatus::options()),
            ])
            ->recordActions([
                self::confirmAction(),
                ActionGroup::make([
                    self::closeAction('done', AppointmentStatus::Done, 'Marquer réalisé', Heroicon::OutlinedCheckCircle),
                    self::closeAction('noShow', AppointmentStatus::NoShow, 'Prospect absent', Heroicon::OutlinedUserMinus),
                    self::closeAction('cancel', AppointmentStatus::Cancelled, 'Annuler', Heroicon::OutlinedXCircle),
                ]),
            ]);
    }

    public static function confirmAction(): Action
    {
        return Action::make('confirm')
            ->label(fn (Appointment $record): string => $record->status === AppointmentStatus::Confirmed ? 'Reprogrammer' : 'Confirmer')
            ->icon(Heroicon::OutlinedCalendarDays)
            ->color('success')
            ->visible(fn (Appointment $record): bool => in_array($record->status, [AppointmentStatus::Requested, AppointmentStatus::Confirmed], true)
                && auth()->user()?->can('update', $record) === true)
            ->modalHeading('Confirmer le rendez-vous')
            ->modalDescription(fn (Appointment $record): string => 'Créneaux souhaités : '.implode(' ; ', $record->slotLabels()).'. Le prospect reçoit un e-mail de confirmation s\'il a donné son adresse.')
            ->fillForm(fn (Appointment $record): array => ['scheduled_at' => $record->scheduled_at, 'notes' => $record->notes])
            ->schema([
                DateTimePicker::make('scheduled_at')->label('Date et heure (Bamako)')->seconds(false)
                    ->timezone(config('kh.display_timezone'))->minDate(now())->required(),
                Textarea::make('notes')->label('Notes internes')->rows(2),
            ])
            ->action(function (Appointment $record, array $data): void {
                LeadActions::run(fn () => app(ConfirmAppointment::class)->handle(
                    $record,
                    Carbon::parse((string) $data['scheduled_at']),
                    LeadActions::user() ?? abort(403),
                    isset($data['notes']) ? (string) $data['notes'] : null,
                ), 'Rendez-vous confirmé');
            });
    }

    private static function closeAction(string $name, AppointmentStatus $to, string $label, Heroicon $icon): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($to === AppointmentStatus::Cancelled ? 'danger' : 'gray')
            ->visible(fn (Appointment $record): bool => auth()->user()?->can('update', $record) === true && match ($to) {
                AppointmentStatus::Cancelled => in_array($record->status, [AppointmentStatus::Requested, AppointmentStatus::Confirmed], true),
                default => $record->status === AppointmentStatus::Confirmed,
            })
            ->schema([Textarea::make('notes')->label('Commentaire')->rows(2)])
            ->action(function (Appointment $record, array $data) use ($to): void {
                LeadActions::run(fn () => app(ConfirmAppointment::class)->close(
                    $record,
                    $to,
                    LeadActions::user() ?? abort(403),
                    isset($data['notes']) ? (string) $data['notes'] : null,
                ), 'Rendez-vous mis à jour');
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppointments::route('/'),
        ];
    }
}
