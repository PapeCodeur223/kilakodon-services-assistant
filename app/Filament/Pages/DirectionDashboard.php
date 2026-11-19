<?php

namespace App\Filament\Pages;

use App\Domain\Platform\Models\User;
use App\Domain\Platform\Services\DirectionMetrics;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Tableau de bord direction (MES-02) : entonnoir, sources, délai de prise en charge, coût IA.
 * Chiffres recalculés depuis les tables sources à chaque affichage.
 */
class DirectionDashboard extends Page
{
    /** Périodes proposées, en jours (pas au-delà de 30 : les diagnostics anonymes sont purgés à 30 jours). */
    public const PERIODS = [7, 30];

    protected string $view = 'filament.pages.direction-dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|UnitEnum|null $navigationGroup = 'Pilotage';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Tableau de bord direction';

    protected static ?string $title = 'Tableau de bord direction';

    protected static ?string $slug = 'direction';

    #[Url(as: 'jours')]
    public int $days = 30;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdmin();
    }

    public function setPeriod(int $days): void
    {
        $this->days = in_array($days, self::PERIODS, true) ? $days : 30;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetrics(): array
    {
        $days = in_array($this->days, self::PERIODS, true) ? $this->days : 30;

        return app(DirectionMetrics::class)->forLastDays($days);
    }
}
