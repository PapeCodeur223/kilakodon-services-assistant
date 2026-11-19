<?php

namespace App\Domain\Platform\Services;

use App\Domain\Assistant\Conversation\AssistantGuard;
use App\Domain\Assistant\Models\AiUsageDaily;
use App\Domain\Brief\Models\Brief;
use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Crm\Models\Lead;
use App\Domain\Diagnostic\Models\DiagnosticSession;
use App\Domain\Platform\Models\AnalyticsEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tableau de bord direction (MES-02) : tout est recalculé depuis les tables sources à chaque
 * affichage — aucun chiffre stocké, aucun chiffre inventé.
 */
class DirectionMetrics
{
    /** Étapes qui supposent un lead qualifié ; un lead passé par « qualifié » puis perdu compte aussi (historique). */
    public const QUALIFIED_STAGES = [LeadStage::Qualified, LeadStage::Meeting, LeadStage::Proposal, LeadStage::Won];

    /**
     * @return array{since: Carbon, funnel: list<array{key: string, label: string, value: int, chain: string}>, sources: list<array{source: string, leads: int, qualified: int, won: int}>, response: array{median_hours: float|null, answered: int, waiting: int}, ai: array{cost_usd: float, qualified_briefs: int, cost_per_qualified_brief_usd: float|null, pricing: bool}}
     */
    public function forLastDays(int $days = 30): array
    {
        $since = now()->subDays($days);

        return [
            'since' => $since,
            'funnel' => $this->funnel($since),
            'sources' => $this->sources($since),
            'response' => $this->response($since),
            'ai' => $this->ai($since),
        ];
    }

    /**
     * Deux chaînes qui s'emboîtent : visites → diagnostic → brief ; leads (tous canaux) → qualifiés → gagnés.
     * Le taux d'une étape n'est calculé qu'au sein de sa chaîne.
     *
     * @return list<array{key: string, label: string, value: int, chain: string}>
     */
    private function funnel(Carbon $since): array
    {
        $views = AnalyticsEvent::query()->where('name', 'page_view')->where('occurred_at', '>=', $since);

        return [
            ['key' => 'page_views', 'label' => 'Pages vues (visiteurs ayant accepté la mesure)', 'value' => (clone $views)->count(), 'chain' => 'audience'],
            ['key' => 'visitors', 'label' => 'Visiteurs distincts (mesure acceptée)', 'value' => (clone $views)->whereNotNull('visitor_key')->distinct()->count('visitor_key'), 'chain' => 'audience'],
            ['key' => 'diagnostics_started', 'label' => 'Diagnostics commencés', 'value' => DiagnosticSession::query()->where('started_at', '>=', $since)->count(), 'chain' => 'diagnostic'],
            ['key' => 'diagnostics_completed', 'label' => 'Diagnostics terminés', 'value' => DiagnosticSession::query()->where('completed_at', '>=', $since)->count(), 'chain' => 'diagnostic'],
            ['key' => 'briefs_validated', 'label' => 'Briefs validés', 'value' => Brief::query()->where('validated_at', '>=', $since)->count(), 'chain' => 'diagnostic'],
            ['key' => 'leads', 'label' => 'Leads créés (tous canaux)', 'value' => $this->leads($since)->count(), 'chain' => 'leads'],
            ['key' => 'qualified', 'label' => 'Leads qualifiés', 'value' => $this->qualified($this->leads($since))->count(), 'chain' => 'leads'],
            ['key' => 'won', 'label' => 'Leads gagnés', 'value' => $this->leads($since)->where('stage', LeadStage::Won)->count(), 'chain' => 'leads'],
        ];
    }

    /**
     * @return list<array{source: string, leads: int, qualified: int, won: int}>
     */
    private function sources(Carbon $since): array
    {
        $qualified = array_map(fn (LeadStage $stage): string => $stage->value, self::QUALIFIED_STAGES);

        return $this->leads($since)
            ->selectRaw('source, count(*) as leads')
            ->selectRaw('sum(case when stage in ('.implode(',', array_fill(0, count($qualified), '?')).') or exists (select 1 from '.DB::getTablePrefix().'lead_stage_history h where h.lead_id = '.DB::getTablePrefix().'leads.id and h.to_stage = ?) then 1 else 0 end) as qualified', [...$qualified, LeadStage::Qualified->value])
            ->selectRaw('sum(case when stage = ? then 1 else 0 end) as won', [LeadStage::Won->value])
            ->groupBy('source')
            ->orderByDesc('leads')
            ->orderBy('source')
            ->limit(10)
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                'source' => (string) $row->source,
                'leads' => (int) $row->leads,
                'qualified' => (int) $row->qualified,
                'won' => (int) $row->won,
            ])
            ->values()
            ->all();
    }

    /**
     * Délai médian entre la création du lead et la première prise en charge (heures calendaires).
     *
     * @return array{median_hours: float|null, answered: int, waiting: int}
     */
    private function response(Carbon $since): array
    {
        $delays = $this->leads($since)
            ->whereNotNull('first_response_at')
            ->get(['created_at', 'first_response_at'])
            ->map(fn (Lead $lead): float => max(0, $lead->created_at?->diffInMinutes($lead->first_response_at, true) ?? 0) / 60)
            ->sort()
            ->values();

        $count = $delays->count();
        $median = match (true) {
            $count === 0 => null,
            $count % 2 === 1 => (float) $delays[intdiv($count, 2)],
            default => ((float) $delays[$count / 2 - 1] + (float) $delays[$count / 2]) / 2,
        };

        return [
            'median_hours' => $median !== null ? round($median, 1) : null,
            'answered' => $count,
            'waiting' => $this->leads($since)->whereNull('first_response_at')->whereNotIn('stage', [LeadStage::Lost, LeadStage::OnHold])->count(),
        ];
    }

    /**
     * Coût IA de la période rapporté aux briefs devenus des leads qualifiés.
     *
     * @return array{cost_usd: float, qualified_briefs: int, cost_per_qualified_brief_usd: float|null, pricing: bool}
     */
    private function ai(Carbon $since): array
    {
        $cost = AiUsageDaily::query()->whereDate('date', '>=', $since->toDateString())->sum('cost_estimate_usd_micros') / 1_000_000;
        $qualifiedBriefs = $this->qualified($this->leads($since)->whereNotNull('brief_id'))->count();
        $pricing = app(AssistantGuard::class)->pricingConfigured();

        return [
            'cost_usd' => round($cost, 4),
            'qualified_briefs' => $qualifiedBriefs,
            'cost_per_qualified_brief_usd' => $pricing && $qualifiedBriefs > 0 ? round($cost / $qualifiedBriefs, 4) : null,
            'pricing' => $pricing,
        ];
    }

    /**
     * @return Builder<Lead>
     */
    private function leads(Carbon $since): Builder
    {
        return Lead::query()->where('created_at', '>=', $since);
    }

    /**
     * @param  Builder<Lead>  $query
     * @return Builder<Lead>
     */
    private function qualified(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereIn('stage', self::QUALIFIED_STAGES)
            ->orWhereHas('stageHistory', fn (Builder $history) => $history->where('to_stage', LeadStage::Qualified)));
    }
}
