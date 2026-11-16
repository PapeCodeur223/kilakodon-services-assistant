<?php

namespace App\Domain\Assistant\Evaluation;

use App\Domain\Assistant\Conversation\AssistantAnswer;
use App\Domain\Assistant\Conversation\PromptBuilder;
use App\Domain\Assistant\Conversation\ResponseValidator;
use App\Domain\Assistant\Conversation\SmallTalk;
use App\Domain\Assistant\Enums\MessageRole;
use App\Domain\Assistant\Exceptions\LlmUnavailableException;
use App\Domain\Assistant\Knowledge\Retriever;
use App\Domain\Assistant\Llm\LlmClient;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Assistant\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Joue un cas du corpus (AI_ASSISTANT §5) sur le vrai pipeline : longueur → recherche → prompt →
 * modèle → validation serveur. Chaque cas s'exécute dans une transaction annulée : rien n'est conservé.
 *
 * @phpstan-type EvalCase array{id: string, category: string, critical?: bool, messages: list<string>, generated_message?: array{char: string, length: int}, fixture_documents?: list<array{title: string, content: string}>, expect?: array<string, mixed>}
 * @phpstan-type EvalResult array{id: string, category: string, critical: bool, answer: string, grounding: string, suggested_action: string, citations: int, rejected: string|null, checks: array<string, bool>, passed: bool}
 */
class AiEvaluator
{
    public const CHECKS = [
        'grounding_in', 'no_money_amount', 'suggested_action_in', 'must_mention_any', 'must_not_mention',
        'citations_min', 'max_words', 'brief_draft_fields_null', 'rejected',
    ];

    public function __construct(
        private readonly Retriever $retriever,
        private readonly PromptBuilder $prompts,
        private readonly LlmClient $llm,
        private readonly ResponseValidator $validator,
        private readonly SmallTalk $smallTalk,
    ) {}

    /**
     * @param  EvalCase  $case
     * @return EvalResult
     */
    public function run(array $case): array
    {
        DB::beginTransaction();

        try {
            [$answer, $rejected] = $this->play($case);
        } finally {
            DB::rollBack();
        }

        $checks = $this->checks((array) ($case['expect'] ?? []), $answer, $rejected);

        return [
            'id' => $case['id'],
            'category' => $case['category'],
            'critical' => (bool) ($case['critical'] ?? false),
            'answer' => $answer->answer ?? '',
            'grounding' => $answer?->grounding->value ?? '',
            'suggested_action' => $answer?->suggestedAction->value ?? '',
            'citations' => $answer !== null ? count($answer->citations) : 0,
            'rejected' => $rejected,
            'checks' => $checks,
            'passed' => ! in_array(false, $checks, true),
        ];
    }

    /**
     * @param  EvalCase  $case
     * @return array{0: AssistantAnswer|null, 1: string|null}
     */
    private function play(array $case): array
    {
        $conversation = Conversation::query()->create(['ip_hash' => str_repeat('0', 64), 'entry_path' => '/']);
        $answer = null;
        $messages = isset($case['generated_message'])
            ? [str_repeat((string) $case['generated_message']['char'], (int) $case['generated_message']['length'])]
            : $case['messages'];

        foreach ($messages as $text) {
            $text = trim((string) $text);

            // Mêmes refus serveur que la garde, sans appel au modèle.
            if ($text === '') {
                return [null, 'empty'];
            }
            if (mb_strlen($text) > (int) config('assistant.guards.max_message_chars', 1000)) {
                return [null, 'too_long'];
            }

            // Comme en production : les formules de politesse ont une réponse fixe, sans modèle.
            if (($courtesy = $this->smallTalk->reply($text)) !== null) {
                Message::query()->create(['conversation_id' => $conversation->id, 'role' => MessageRole::User, 'content' => $text]);
                Message::query()->create(['conversation_id' => $conversation->id, 'role' => MessageRole::Assistant, 'content' => $courtesy->answer]);
                $answer = $courtesy;

                continue;
            }

            $documents = [...$this->retriever->search($text), ...$this->fixtures($case)];
            $request = $this->prompts->build($conversation, $text, $documents, true);
            Message::query()->create(['conversation_id' => $conversation->id, 'role' => MessageRole::User, 'content' => $text]);

            try {
                $answer = $this->validator->validate($this->llm->complete($request)->toolInput, $documents, $text);
            } catch (LlmUnavailableException) {
                return [null, 'llm_unavailable'];
            }

            Message::query()->create(['conversation_id' => $conversation->id, 'role' => MessageRole::Assistant, 'content' => $answer->answer, 'error_code' => $answer->errorCode]);
        }

        return [$answer, null];
    }

    /**
     * Documents de test injectés (ex. document piégé inj-05) : identifiants négatifs, jamais en base.
     *
     * @param  EvalCase  $case
     * @return list<array{id: int, document_id: int, title: string, heading: string, content: string, url: string, score: float}>
     */
    private function fixtures(array $case): array
    {
        $documents = [];
        foreach ($case['fixture_documents'] ?? [] as $i => $doc) {
            $documents[] = [
                'id' => -1 - $i, 'document_id' => -1 - $i, 'title' => $doc['title'], 'heading' => $doc['title'],
                'content' => $doc['content'], 'url' => url('/'), 'score' => 1.0,
            ];
        }

        return $documents;
    }

    /**
     * @param  array<string, mixed>  $expect
     * @return array<string, bool>
     */
    private function checks(array $expect, ?AssistantAnswer $answer, ?string $rejected): array
    {
        $results = [];

        if (array_key_exists('rejected', $expect)) {
            $results['rejected'] = (bool) $expect['rejected'] === ($rejected !== null && $rejected !== 'llm_unavailable');
        }

        if ($answer === null) {
            // Refus serveur attendu : les autres contrôles portent sur une réponse qui n'existe pas.
            if (! array_key_exists('rejected', $expect)) {
                $results['answered'] = false;
            }

            return $results;
        }

        $text = Str::lower(Str::ascii($answer->answer.' '.($answer->clarifyingQuestion ?? '')));
        $contains = fn (string $needle): bool => str_contains($text, Str::lower(Str::ascii($needle)));

        foreach ($expect as $check => $value) {
            $results[$check] = match ($check) {
                'grounding_in' => in_array($answer->grounding->value, (array) $value, true),
                'suggested_action_in' => in_array($answer->suggestedAction->value, (array) $value, true),
                'no_money_amount' => ! (bool) $value || $this->validator->amounts($answer->answer) === [] || $answer->errorCode === 'price_guard',
                'must_mention_any' => collect((array) $value)->contains(fn (string $n): bool => $contains($n)),
                'must_not_mention' => ! collect((array) $value)->contains(fn (string $n): bool => $contains($n)),
                'citations_min' => count($answer->citations) >= (int) $value,
                'max_words' => str_word_count(Str::ascii($answer->answer)) <= (int) $value,
                'brief_draft_fields_null' => $answer->briefDraft === null
                    || collect((array) $value)->every(fn (string $field): bool => ($answer->briefDraft[$field] ?? null) === null),
                'rejected' => $results['rejected'] ?? false,
                default => false, // contrôle inconnu : le cas est à corriger
            };
        }

        return $results;
    }
}
