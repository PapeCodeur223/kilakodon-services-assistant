<?php

namespace App\Filament\Widgets;

use App\Domain\Brief\Enums\BriefStatus;
use App\Domain\Brief\Models\Brief;
use App\Domain\Crm\Enums\AppointmentStatus;
use App\Domain\Crm\Models\Appointment;
use App\Domain\Crm\Models\Lead;
use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\User;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Briefs\BriefResource;
use App\Filament\Resources\Leads\LeadResource;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Indicateurs commerciaux recalculés depuis les tables sources (aucun chiffre saisi à la main).
 */
class CrmOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasRole(UserRole::Admin, UserRole::Sales);
    }

    protected function getStats(): array
    {
        $since = now()->subDays(30);
        $newLeads = Lead::query()->where('created_at', '>=', $since)->count();
        $daily = collect(range(13, 0))->map(fn (int $d): int => Lead::query()->whereDate('created_at', now()->subDays($d)->toDateString())->count())->all();

        return [
            Stat::make('Leads à traiter', (string) Lead::query()->toHandle()->count())
                ->description('Sans prochaine action ou action échue')
                ->descriptionIcon(Heroicon::OutlinedExclamationCircle)
                ->color('danger')
                ->url(LeadResource::getUrl('index', ['filters' => ['to_handle' => ['isActive' => true]]])),
            Stat::make('Nouveaux leads (30 jours)', (string) $newLeads)
                ->description('Toutes origines confondues')
                ->chart($daily)
                ->color('primary')
                ->url(LeadResource::getUrl('kanban')),
            Stat::make('Rendez-vous à confirmer', (string) Appointment::query()->where('status', AppointmentStatus::Requested)->count())
                ->description('Demandés par des prospects')
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color('warning')
                ->url(AppointmentResource::getUrl('index')),
            Stat::make('Briefs validés (30 jours)', (string) Brief::query()->where('status', BriefStatus::Validated)->where('validated_at', '>=', $since)->count())
                ->description('Issus du diagnostic')
                ->descriptionIcon(Heroicon::OutlinedDocumentCheck)
                ->color('success')
                ->url(BriefResource::getUrl('index')),
        ];
    }
}
