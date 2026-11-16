<?php

namespace App\Domain\Assistant\Actions;

use App\Domain\Assistant\Conversation\AssistantGuard;
use App\Domain\Platform\Actions\QueueOutboundMessage;
use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\OutboundMessage;
use App\Domain\Platform\Models\User;

/**
 * Surveillance du budget quotidien (TRD §7.4, §10 — toutes les 15 min) : alerte e-mail aux
 * administrateurs à 80 %, une seule fois par jour (Bamako). Le blocage à 100 % est fait par la garde.
 */
class CheckAssistantBudget
{
    public const TEMPLATE = 'ai_budget_alert';

    public function __construct(
        private readonly AssistantGuard $guard,
        private readonly QueueOutboundMessage $queue,
    ) {}

    /**
     * @return array{budget_micros: int, spent_micros: int, ratio: float|null, alerted: bool}
     */
    public function handle(): array
    {
        $budget = $this->guard->dailyBudgetMicros();
        $spent = $this->guard->spentTodayMicros();
        $ratio = $budget > 0 ? $spent / $budget : null;
        $date = now(config('kh.display_timezone'))->toDateString();
        $threshold = (float) config('assistant.guards.budget_alert_ratio', 0.8);

        $alerted = false;
        if ($ratio !== null && $ratio >= $threshold && ! $this->alreadyAlerted($date)) {
            $payload = ['date' => $date, 'budget_usd' => round($budget / 1_000_000, 2), 'spent_usd' => round($spent / 1_000_000, 2), 'percent' => (int) floor($ratio * 100)];

            foreach (User::query()->where('role', UserRole::Admin)->where('is_active', true)->pluck('email') as $email) {
                $this->queue->handle((string) $email, self::TEMPLATE, $payload);
            }
            $alerted = true;
        }

        return ['budget_micros' => $budget, 'spent_micros' => $spent, 'ratio' => $ratio, 'alerted' => $alerted];
    }

    private function alreadyAlerted(string $date): bool
    {
        return OutboundMessage::query()
            ->where('template', self::TEMPLATE)
            ->where('created_at', '>=', now(config('kh.display_timezone'))->startOfDay()->utc())
            ->get(['payload'])
            ->contains(fn (OutboundMessage $m): bool => ($m->payload['date'] ?? null) === $date);
    }
}
