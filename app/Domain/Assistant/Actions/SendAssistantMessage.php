<?php

namespace App\Domain\Assistant\Actions;

use App\Domain\Assistant\Conversation\AssistantAnswer;
use App\Domain\Assistant\Conversation\AssistantGuard;
use App\Domain\Assistant\Conversation\AssistantReply;
use App\Domain\Assistant\Conversation\FallbackTexts;
use App\Domain\Assistant\Conversation\PromptBuilder;
use App\Domain\Assistant\Conversation\ResponseValidator;
use App\Domain\Assistant\Conversation\SmallTalk;
use App\Domain\Assistant\Enums\ConversationStatus;
use App\Domain\Assistant\Enums\MessageRole;
use App\Domain\Assistant\Enums\SuggestedAction;
use App\Domain\Assistant\Exceptions\LlmUnavailableException;
use App\Domain\Assistant\Knowledge\Retriever;
use App\Domain\Assistant\Llm\LlmClient;
use App\Domain\Assistant\Llm\LlmResponse;
use App\Domain\Assistant\Models\AiUsageDaily;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Assistant\Models\Message;
use App\Domain\Crm\Support\BusinessHours;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Un message du visiteur → gardes → recherche → prompt → modèle → validation serveur → enregistrement.
 * Le modèle ne fait que rédiger : la réponse enregistrée et affichée est celle du validateur, et une
 * action proposée (brief, rendez-vous) n'écrit rien tant que le visiteur ne l'a pas confirmée.
 */
class SendAssistantMessage
{
    public function __construct(
        private readonly AssistantGuard $guard,
        private readonly Retriever $retriever,
        private readonly PromptBuilder $prompts,
        private readonly LlmClient $llm,
        private readonly ResponseValidator $validator,
        private readonly ProposeAssistantAction $proposals,
        private readonly SmallTalk $smallTalk,
    ) {}

    public function handle(Conversation $conversation, string $text): AssistantReply
    {
        $text = trim($text);

        if (($failure = $this->guard->checkMessage($conversation, $text)) !== null) {
            $this->usage()->increment('blocked_requests');

            return AssistantReply::blocked($failure);
        }

        // Salutations, remerciements, identité… : réponse fixe, sans appel au modèle ni coût (D-021).
        if (($courtesy = $this->smallTalk->reply($text)) !== null) {
            $started = hrtime(true);
            $this->store($conversation, ['role' => MessageRole::User, 'content' => $text]);

            return $this->reply($conversation, $courtesy, null, $started, SmallTalk::ENGINE);
        }

        $documents = $this->retriever->search($text);
        // Construit AVANT d'enregistrer le message : l'historique ne doit pas le contenir deux fois.
        $request = $this->prompts->build($conversation, $text, $documents, BusinessHours::isOpen());

        $this->store($conversation, ['role' => MessageRole::User, 'content' => $text]);

        $started = hrtime(true);
        try {
            $response = $this->llm->complete($request);
        } catch (LlmUnavailableException $e) {
            Log::warning('Assistant : modèle indisponible.', ['conversation' => $conversation->id, 'error' => $e->getMessage()]);

            return $this->reply($conversation, AssistantAnswer::fallback(FallbackTexts::UNAVAILABLE, 'llm_unavailable'), null, $started);
        } catch (Throwable $e) {
            report($e);

            return $this->reply($conversation, AssistantAnswer::fallback(FallbackTexts::UNAVAILABLE, 'llm_error'), null, $started);
        }

        $answer = $this->validator->validate($response->toolInput, $documents, $text);

        return $this->reply($conversation, $answer, $response, $started);
    }

    private function reply(Conversation $conversation, AssistantAnswer $answer, ?LlmResponse $response, int|float $started, ?string $engine = null): AssistantReply
    {
        $latency = (int) round((hrtime(true) - $started) / 1_000_000);
        $tokensIn = $response->inputTokens ?? 0;
        $tokensOut = $response->outputTokens ?? 0;
        $cost = null;
        if ($response !== null) {
            // L'API peut renvoyer un identifiant daté (« claude-…-2026xxxx ») : sans tarif propre, on prend celui
            // du modèle demandé, sinon le plafond budgétaire ne verrait jamais la dépense.
            $cost = $this->guard->costMicros($response->model, $tokensIn, $tokensOut)
                ?? $this->guard->costMicros((string) config('assistant.model'), $tokensIn, $tokensOut);

            if ($cost === null && config('assistant.driver') === 'anthropic') {
                Log::warning('Assistant : coût non calculable (tarif absent).', ['model' => $response->model]);
            }
        }

        $message = $this->store($conversation, [
            'role' => MessageRole::Assistant,
            'content' => $answer->answer,
            'citations' => $answer->citations !== [] ? $answer->citations : null,
            'grounding' => $answer->grounding,
            'suggested_action' => $answer->suggestedAction,
            'model' => $response->model ?? $engine,
            'prompt_version' => $engine ?? $this->prompts->version(),
            'tokens_in' => $response !== null ? $tokensIn : null,
            'tokens_out' => $response !== null ? $tokensOut : null,
            'latency_ms' => $latency,
            'error_code' => $answer->errorCode,
        ], $tokensIn, $tokensOut, $cost ?? 0);

        if ($response !== null) {
            $usage = $this->usage();
            AiUsageDaily::query()->whereKey($usage->id)->update([
                'requests' => DB::raw('requests + 1'),
                'tokens_in' => DB::raw('tokens_in + '.$tokensIn),
                'tokens_out' => DB::raw('tokens_out + '.$tokensOut),
                'cost_estimate_usd_micros' => DB::raw('cost_estimate_usd_micros + '.($cost ?? 0)),
            ]);
        }

        if ($answer->suggestedAction === SuggestedAction::HandoffWhatsapp && $answer->errorCode === null) {
            Conversation::query()->whereKey($conversation->id)->update(['status' => ConversationStatus::HandedOff]);
        }

        $proposal = in_array($answer->suggestedAction, [SuggestedAction::ProposeBrief, SuggestedAction::RequestAppointment], true)
            ? $this->proposals->fromAnswer($conversation, $message, $answer)
            : null;

        return AssistantReply::answered($message, $answer, $proposal);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function store(Conversation $conversation, array $attributes, int $tokensIn = 0, int $tokensOut = 0, int $cost = 0): Message
    {
        return DB::transaction(function () use ($conversation, $attributes, $tokensIn, $tokensOut, $cost): Message {
            $message = Message::query()->create([...$attributes, 'conversation_id' => $conversation->id]);

            Conversation::query()->whereKey($conversation->id)->update([
                'message_count' => DB::raw('message_count + 1'),
                'tokens_in' => DB::raw('tokens_in + '.$tokensIn),
                'tokens_out' => DB::raw('tokens_out + '.$tokensOut),
                'cost_estimate_usd_micros' => DB::raw('cost_estimate_usd_micros + '.$cost),
                'last_message_at' => now(),
            ]);

            return $message;
        });
    }

    private function usage(): AiUsageDaily
    {
        return AiUsageDaily::today();
    }
}
