<?php

namespace App\Domain\Assistant\Conversation;

use App\Domain\Assistant\Enums\Grounding;
use App\Domain\Assistant\Enums\SuggestedAction;
use App\Domain\Assistant\Knowledge\Retriever;

/**
 * Validation serveur de la sortie `respond` (AI_ASSISTANT §3) :
 * - structure conforme, sinon repli `invalid_output` ;
 * - citations limitées aux fragments réellement fournis (sinon `sourced` → `partial`) ;
 * - filtre montants : un montant absent des documents ET de la question du visiteur → repli prix (`price_guard`) ;
 * - `brief_draft` accepté seulement avec `propose_brief`.
 *
 * @phpstan-import-type Retrieved from Retriever
 * @phpstan-import-type BriefDraft from AssistantAnswer
 */
class ResponseValidator
{
    /** Montants écrits en lettres (« cinq cent mille francs ») : comparés tels quels aux sources. */
    public const WORD_MONEY_PATTERN = '/\b(?:cents?|mille|millions?|milliards?)\s+(?:de\s+)?(?:francs?|fcfa|f\s?cfa|euros?|dollars?)\b/iu';

    /** Délais annoncés (« sous 1 h », « dans l'heure ») : jamais d'engagement absent des sources (IA-04). */
    public const DELAY_PATTERN = '/\b(?:sous|dans|en moins de|d\'ici)\s+\d+\s*(?:h|heures?|minutes?|min|jours?)\b|\bdans l\'heure\b/iu';

    public const MONEY_PATTERN = '/\d[\d\s.,]*\s?(?:fcfa|f\s?cfa|xof|francs?|usd|eur(?:os?)?|k|m|f)(?![\p{L}\d])|\d[\d\s.,]*\s?[€\$]|[€\$]\s?\d[\d\s.,]*|\d[\d\s.,]*\s?millions?\b|\bmillions?\s+de\s+(?:fcfa|francs)/iu';

    /**
     * @param  array<string, mixed>|null  $input  arguments de l'outil `respond`
     * @param  list<Retrieved>  $documents  fragments fournis au modèle
     * @param  string  $question  message du visiteur (un montant qu'il cite peut être repris)
     */
    public function validate(?array $input, array $documents, string $question = ''): AssistantAnswer
    {
        if (! $this->wellFormed($input)) {
            return AssistantAnswer::fallback(FallbackTexts::INVALID_OUTPUT, 'invalid_output');
        }

        /** @var array{answer: string, grounding: string, citations: list<int|string>, suggested_action: string, clarifying_question?: string|null, brief_draft?: array<string, mixed>|null} $input */
        $answer = trim($input['answer']);
        $grounding = Grounding::from($input['grounding']);
        $action = SuggestedAction::from($input['suggested_action']);

        $clarifying = isset($input['clarifying_question']) && trim($input['clarifying_question']) !== ''
            ? mb_substr(trim($input['clarifying_question']), 0, 300)
            : null;

        // Tout texte affiché est filtré : la réponse ET la question de clarification.
        if ($this->inventsAmount($answer.' '.($clarifying ?? ''), $documents, $question)) {
            return AssistantAnswer::fallback(FallbackTexts::PRICE, 'price_guard', SuggestedAction::StartDiagnostic);
        }

        if ($this->promisesDelay($answer.' '.($clarifying ?? ''), $documents)) {
            return AssistantAnswer::fallback(FallbackTexts::INVALID_OUTPUT, 'commitment_guard');
        }

        $byId = [];
        foreach ($documents as $doc) {
            $byId[$doc['id']] = $doc;
        }

        $citations = [];
        foreach (array_slice(array_unique(array_map('intval', $input['citations'])), 0, 4) as $id) {
            if (isset($byId[$id])) {
                $citations[] = ['chunk_id' => $id, 'url' => $byId[$id]['url'], 'title' => $byId[$id]['title']];
            }
        }

        if ($grounding === Grounding::Sourced && $citations === []) {
            $grounding = Grounding::Partial;
        }

        return new AssistantAnswer(
            answer: mb_substr($answer, 0, 1500),
            grounding: $grounding,
            citations: $citations,
            suggestedAction: $action,
            clarifyingQuestion: $clarifying,
            briefDraft: $action === SuggestedAction::ProposeBrief ? $this->briefDraft($input['brief_draft'] ?? null, $documents, $question) : null,
        );
    }

    /**
     * Montants présents dans un texte (forme normalisée, chiffres seuls), pour comparaison.
     *
     * @return list<string>
     */
    public function amounts(string $text): array
    {
        preg_match_all(self::MONEY_PATTERN, $text, $matches);

        return array_values(array_unique(array_filter(array_map(
            fn (string $m): string => (string) preg_replace('/\D+/', '', $m),
            $matches[0],
        ), fn (string $digits): bool => $digits !== '')));
    }

    /**
     * @param  list<Retrieved>  $documents
     */
    private function promisesDelay(string $text, array $documents): bool
    {
        $sources = implode(' ', array_column($documents, 'content'));
        preg_match_all(self::DELAY_PATTERN, $text, $matches);

        foreach ($matches[0] as $phrase) {
            if (mb_stripos($sources, $phrase) === false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<Retrieved>  $documents
     */
    private function inventsAmount(string $answer, array $documents, string $question): bool
    {
        $allowedText = $question.' '.implode(' ', array_column($documents, 'content'));

        preg_match_all(self::WORD_MONEY_PATTERN, $answer, $words);
        foreach ($words[0] as $phrase) {
            if (mb_stripos($allowedText, $phrase) === false) {
                return true;
            }
        }

        $found = $this->amounts($answer);
        if ($found === [] && preg_match('/\bmillions?\b/iu', $answer) !== 1) {
            return false;
        }

        $allowed = $this->amounts($allowedText);

        // « millions » sans chiffre : toléré seulement s'il figure dans les sources ou la question.
        if ($found === []) {
            return preg_match('/\bmillions?\b/iu', $allowedText) !== 1;
        }

        return array_diff($found, $allowed) !== [];
    }

    /**
     * @param  array<string, mixed>|null  $input
     */
    private function wellFormed(?array $input): bool
    {
        if ($input === null) {
            return false;
        }

        $answer = $input['answer'] ?? null;
        $citations = $input['citations'] ?? null;

        return is_string($answer) && trim($answer) !== '' && mb_strlen($answer) <= 1500
            && is_string($input['grounding'] ?? null) && Grounding::tryFrom($input['grounding']) !== null
            && is_array($citations) && count($citations) <= 4 && array_filter($citations, fn (mixed $c): bool => ! is_int($c) && ! (is_string($c) && ctype_digit($c))) === []
            && is_string($input['suggested_action'] ?? null) && SuggestedAction::tryFrom($input['suggested_action']) !== null
            && (! isset($input['brief_draft']) || is_array($input['brief_draft']))
            && (! isset($input['clarifying_question']) || is_string($input['clarifying_question']));
    }

    /**
     * Seuls les champs prévus ; une valeur absente reste null (jamais d'invention de l'assistant).
     * Un champ qui contient un montant absent des sources et du message du visiteur est vidé :
     * la carte de confirmation ne doit jamais arriver pré-remplie avec un prix inventé.
     *
     * @param  list<Retrieved>  $documents
     * @return BriefDraft|null
     */
    private function briefDraft(mixed $draft, array $documents, string $question): ?array
    {
        if (! is_array($draft)) {
            return null;
        }

        $safe = fn (string $text): bool => ! $this->inventsAmount($text, $documents, $question);

        $clean = [];
        foreach (RespondTool::BRIEF_FIELDS as $field) {
            $value = $draft[$field] ?? null;
            $value = is_string($value) && trim($value) !== '' ? mb_substr(trim($value), 0, 300) : null;
            $clean[$field] = $value !== null && $safe($value) ? $value : null;
        }
        $summary = is_string($draft['summary'] ?? null) ? mb_substr(trim($draft['summary']), 0, 600) : '';
        $summary = $safe($summary) ? $summary : '';

        return [...$clean, 'summary' => $summary];
    }
}
