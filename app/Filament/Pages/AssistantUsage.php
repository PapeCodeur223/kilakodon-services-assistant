<?php

namespace App\Filament\Pages;

use App\Domain\Assistant\Conversation\AssistantGuard;
use App\Domain\Assistant\Models\AiUsageDaily;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Platform\Models\User;
use App\Domain\Platform\Services\Settings as SettingsService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * « Usage » de l'assistant (admin) : état de l'interrupteur, budget, consommation du jour et du mois.
 * Le coût n'est affiché que si le tarif du modèle est renseigné (jamais de valeur inventée).
 */
class AssistantUsage extends Page
{
    protected string $view = 'filament.pages.assistant-usage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Assistant';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Usage';

    protected static ?string $title = 'Usage de l\'assistant';

    protected static ?string $slug = 'usage-ia';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdmin();
    }

    /**
     * @return array{enabled: bool, reason: string|null, driver: string, model: string, pricing: bool, budget_usd: float, spent_today_usd: float, ratio: float|null}
     */
    public function getStatus(): array
    {
        $guard = app(AssistantGuard::class);
        $budget = $guard->dailyBudgetMicros();
        $spent = $guard->spentTodayMicros();
        $failure = $guard->unavailableReason();

        return [
            'enabled' => $failure === null,
            'reason' => match ($failure?->reason) {
                null => null,
                'budget' => $guard->monthlyBudgetMicros() > 0 && $guard->spentThisMonthMicros() >= $guard->monthlyBudgetMicros()
                    ? 'Budget du mois atteint : l\'assistant reprend le mois prochain, ou plus tôt si vous relevez le plafond.'
                    : 'Budget du jour atteint : l\'assistant reprend demain.',
                default => match (true) {
                    ! (bool) config('assistant.enabled') => 'Coupé par la variable d\'environnement AI_ENABLED.',
                    ! (bool) app(SettingsService::class)->get('ai.enabled', false) => 'Coupé par l\'interrupteur des paramètres.',
                    default => 'API réelle : le budget quotidien et le tarif du modèle doivent être renseignés.',
                },
            },
            'driver' => (string) config('assistant.driver'),
            'model' => (string) config('assistant.model'),
            'pricing' => $guard->pricingConfigured(),
            'budget_usd' => $budget / 1_000_000,
            'spent_today_usd' => $spent / 1_000_000,
            'ratio' => $budget > 0 ? $spent / $budget : null,
        ];
    }

    /**
     * @return array{requests: int, blocked: int, tokens_in: int, tokens_out: int, cost_usd: float, conversations: int, budget_usd: float}
     */
    public function getMonth(): array
    {
        $start = now(config('kh.display_timezone'))->startOfMonth();
        $rows = AiUsageDaily::query()->whereDate('date', '>=', $start->toDateString())->get();

        return [
            'requests' => (int) $rows->sum('requests'),
            'blocked' => (int) $rows->sum('blocked_requests'),
            'tokens_in' => (int) $rows->sum('tokens_in'),
            'tokens_out' => (int) $rows->sum('tokens_out'),
            'cost_usd' => $rows->sum('cost_estimate_usd_micros') / 1_000_000,
            'conversations' => Conversation::query()->where('created_at', '>=', $start->copy()->utc())->count(),
            'budget_usd' => app(AssistantGuard::class)->monthlyBudgetMicros() / 1_000_000,
        ];
    }

    /**
     * @return list<AiUsageDaily>
     */
    public function getDays(): array
    {
        return AiUsageDaily::query()->orderByDesc('date')->limit(31)->get()->all();
    }
}
