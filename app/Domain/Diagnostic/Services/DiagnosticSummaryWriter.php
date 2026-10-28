<?php

namespace App\Domain\Diagnostic\Services;

use App\Domain\Assistant\Exceptions\LlmUnavailableException;
use App\Domain\Assistant\Llm\LlmClient;
use App\Domain\Assistant\Llm\LlmRequest;
use App\Domain\Assistant\Llm\LlmResponse;
use App\Domain\Diagnostic\Engine\DiagnosticOutcome;
use App\Domain\Platform\Services\Settings;

/**
 * Résumé du diagnostic (TRD §6) : gabarit déterministe, ou reformulation IA du DTO seul.
 * Le modèle ne reçoit ni coordonnées ni texte libre du prospect ; sa sortie est validée
 * (longueur, aucun montant ni promesse) et rejetée au moindre doute.
 */
class DiagnosticSummaryWriter
{
    public const PROMPT_VERSION = 'summary-v1';

    public const MIN_WORDS = 60;

    public const MAX_WORDS = 160;

    private const FORBIDDEN = [
        '/\d[\d\s.,]*\s*(f\s?cfa|xof|francs?|€|\$|usd|eur)\b/iu',
        '/\b(prix|tarifs?|garanti(e|es|s)?|gratuitement|remise)\b/iu',
        '/<[^>]+>/',
        '/\d{2,}/',
    ];

    public function __construct(
        private readonly LlmClient $llm,
        private readonly Settings $settings,
    ) {}

    public function template(DiagnosticOutcome $outcome): string
    {
        // Texte brut : il sera échappé à l'affichage, on retire donc l'échappement HTML du gabarit.
        $text = html_entity_decode(view('diagnostic.summary-template', ['outcome' => $outcome])->render(), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    public function aiEnabled(): bool
    {
        return (bool) config('assistant.enabled')
            && (bool) $this->settings->get('ai.enabled', false)
            && (float) $this->settings->get('ai.daily_budget_usd', 0) > 0;
    }

    /**
     * @return LlmResponse|null null si l'IA est coupée, en échec ou si sa sortie est rejetée
     */
    public function ai(DiagnosticOutcome $outcome): ?LlmResponse
    {
        if (! $this->aiEnabled()) {
            return null;
        }

        try {
            $response = $this->llm->complete(new LlmRequest(
                system: $this->prompt(),
                messages: [['role' => 'user', 'content' => "<diagnostic>\n".$this->payload($outcome)."\n</diagnostic>"]],
                maxTokens: 400,
                purpose: 'diagnostic_summary',
            ));
        } catch (LlmUnavailableException) {
            return null;
        }

        $text = trim((string) preg_replace('/\s+/u', ' ', $response->text));

        return $this->isAcceptable($text) ? new LlmResponse($text, $response->model, $response->inputTokens, $response->outputTokens) : null;
    }

    public function isAcceptable(string $text): bool
    {
        $words = str_word_count((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text));

        if ($words < self::MIN_WORDS || $words > self::MAX_WORDS) {
            return false;
        }

        foreach (self::FORBIDDEN as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Uniquement le DTO, avec ses libellés : aucune donnée personnelle.
     */
    public function payload(DiagnosticOutcome $outcome): string
    {
        return (string) json_encode([
            'maturity_level' => $outcome->maturityLevel,
            'maturity_label' => $outcome->maturityLabel(),
            'maturity_explanation' => $outcome->maturityExplanation(),
            'pains' => array_map(fn (array $p): array => ['label' => $p['label'], 'description' => $p['description']], $outcome->pains),
            'recommendations' => array_map(fn (array $r): array => [
                'label' => $r['label'],
                'reasons' => array_values(array_filter(array_column($r['reasons'], 'explanation'))),
            ], $outcome->recommendations),
            'clarifications' => array_column($outcome->clarifications, 'label'),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function prompt(): string
    {
        return (string) file_get_contents(resource_path('prompts/diagnostic/'.self::PROMPT_VERSION.'.md'));
    }
}
