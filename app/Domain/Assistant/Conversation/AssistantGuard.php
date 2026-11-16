<?php

namespace App\Domain\Assistant\Conversation;

use App\Domain\Assistant\Enums\MessageRole;
use App\Domain\Assistant\Models\AiUsageDaily;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Assistant\Models\Message;
use App\Domain\Platform\Services\Settings;
use Illuminate\Support\Carbon;

/**
 * Gardes de l'assistant (TRD §7.4), vérifiées AVANT tout appel au modèle : une garde qui bloque
 * renvoie un repli fixe, jamais une fausse réponse. Le débit par IP est limité par le middleware
 * `throttle:assistant` (6 requêtes par minute).
 *
 * Choix prudent (D-019) : avec le pilote `anthropic`, l'assistant reste coupé tant que le budget
 * quotidien et les tarifs du modèle ne sont pas renseignés, faute de pouvoir plafonner la dépense.
 */
class AssistantGuard
{
    public function __construct(private readonly Settings $settings) {}

    /**
     * Interrupteurs (paramètre admin ET variable d'environnement) et configuration du coût.
     */
    public function enabled(): bool
    {
        return $this->unavailableReason() === null;
    }

    public function unavailableReason(): ?GuardFailure
    {
        if (! (bool) config('assistant.enabled') || ! (bool) $this->settings->get('ai.enabled', false)) {
            return GuardFailure::disabled();
        }

        if (config('assistant.driver') === 'anthropic' && ($this->dailyBudgetMicros() <= 0 || ! $this->pricingConfigured())) {
            return GuardFailure::disabled();
        }

        if ($this->budgetReached()) {
            return GuardFailure::budget();
        }

        return null;
    }

    /**
     * Nouvelle conversation : 5 par visiteur et par jour (clé visiteur), 20 par IP hachée (D-019).
     */
    public function checkStart(?string $visitorKey, string $ipHash): ?GuardFailure
    {
        if (($failure = $this->unavailableReason()) !== null) {
            return $failure;
        }

        $since = $this->startOfDay();
        $perVisitor = (int) config('assistant.guards.conversations_per_visitor_per_day', 5);
        // Les opérateurs mobiles partagent largement les IP : on compte d'abord par visiteur, avec un
        // plafond par IP plus large qui freine seulement les clients sans cookie.
        $perIp = $perVisitor * (int) config('assistant.guards.ip_multiplier', 4);

        $byVisitor = $visitorKey !== null
            ? Conversation::query()->where('created_at', '>=', $since)->where('visitor_key', $visitorKey)->count()
            : 0;
        $byIp = Conversation::query()->where('created_at', '>=', $since)->where('ip_hash', $ipHash)->count();

        return $byVisitor >= $perVisitor || $byIp >= ($visitorKey !== null ? $perIp : $perVisitor)
            ? GuardFailure::visitorLimit()
            : null;
    }

    /**
     * Nouveau message : interrupteurs, budget, message vide ou trop long, messages par conversation.
     */
    public function checkMessage(Conversation $conversation, string $text): ?GuardFailure
    {
        if (($failure = $this->unavailableReason()) !== null) {
            return $failure;
        }

        $text = trim($text);
        if ($text === '') {
            return GuardFailure::empty();
        }
        if (mb_strlen($text) > (int) config('assistant.guards.max_message_chars', 1000)) {
            return GuardFailure::tooLong();
        }

        $sent = Message::query()->where('conversation_id', $conversation->id)->where('role', MessageRole::User)->count();
        if ($sent >= $this->maxMessages()) {
            return GuardFailure::messageLimit();
        }

        return null;
    }

    public function maxMessages(): int
    {
        return max(1, (int) $this->settings->get('ai.max_messages_per_conversation', 20));
    }

    /**
     * Budget quotidien en millionièmes de dollar (0 = non fixé).
     */
    public function dailyBudgetMicros(): int
    {
        return (int) round(((float) $this->settings->get('ai.daily_budget_usd', 0)) * 1_000_000);
    }

    public function budgetReached(): bool
    {
        $budget = $this->dailyBudgetMicros();

        // Budget non fixé : seul le pilote factice (coût nul) fonctionne, cf. unavailableReason().
        if ($budget <= 0) {
            return false;
        }

        return $this->spentTodayMicros() >= $budget;
    }

    public function spentTodayMicros(): int
    {
        $today = now(config('kh.display_timezone'))->toDateString();

        return (int) AiUsageDaily::query()->whereDate('date', $today)->value('cost_estimate_usd_micros');
    }

    public function pricingConfigured(?string $model = null): bool
    {
        $price = config('assistant.pricing.'.($model ?? config('assistant.model')));

        return is_array($price) && is_numeric($price['input'] ?? null) && is_numeric($price['output'] ?? null);
    }

    /**
     * Coût estimé d'un appel, en millionièmes de dollar (prix exprimés en dollars par million de jetons),
     * ou null si le tarif du modèle n'est pas renseigné.
     */
    public function costMicros(string $model, int $tokensIn, int $tokensOut): ?int
    {
        if (! $this->pricingConfigured($model)) {
            return null;
        }

        /** @var array{input: float|int, output: float|int} $price */
        $price = config('assistant.pricing.'.$model);

        return (int) round($tokensIn * (float) $price['input'] + $tokensOut * (float) $price['output']);
    }

    private function startOfDay(): Carbon
    {
        return now(config('kh.display_timezone'))->startOfDay()->utc();
    }
}
