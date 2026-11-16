<?php

namespace App\Http\Controllers\Public;

use App\Domain\Assistant\Actions\CancelAssistantAction;
use App\Domain\Assistant\Actions\ConfirmAssistantAction;
use App\Domain\Assistant\Actions\ProposeAssistantAction;
use App\Domain\Assistant\Actions\RecordAssistantFeedback;
use App\Domain\Assistant\Actions\SendAssistantMessage;
use App\Domain\Assistant\Actions\StartConversation;
use App\Domain\Assistant\Conversation\GuardFailure;
use App\Domain\Assistant\Enums\FeedbackRating;
use App\Domain\Assistant\Enums\MessageRole;
use App\Domain\Assistant\Enums\SuggestedAction;
use App\Domain\Assistant\Models\AiActionProposal;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Assistant\Models\Message;
use App\Domain\Content\Support\PendingValidation;
use App\Domain\Crm\Support\BusinessHours;
use App\Domain\Platform\Services\Settings;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * API interne de l'assistant (TRD §5.2) : JSON, même origine, CSRF et limitation de débit.
 * Contrôleur mince : chaque route appelle une Action. Une conversation n'est accessible qu'à la session
 * qui l'a ouverte (son ULID ne suffit pas) ; une proposition, qu'à la session de sa conversation.
 */
class AssistantController extends Controller
{
    public const VISITOR_COOKIE = 'kh_vid';

    private const SESSION_KEY = 'assistant.conversations';

    public function __construct(private readonly Settings $settings) {}

    public function start(Request $request, StartConversation $start): JsonResponse
    {
        $data = $request->validate(['entry_path' => ['nullable', 'string', 'max:255']]);

        $visitorKey = $request->cookie(self::VISITOR_COOKIE) ?? $request->session()->get('assistant.visitor');
        $visitorKey = is_string($visitorKey) && Str::isUuid($visitorKey) ? $visitorKey : (string) Str::uuid();
        $request->session()->put('assistant.visitor', $visitorKey);
        Cookie::queue(self::VISITOR_COOKIE, $visitorKey, 60 * 24 * 365, null, null, null, true, false, 'Lax');

        $result = $start->handle($visitorKey, (string) $request->ip(), $data['entry_path'] ?? null);

        if ($result instanceof GuardFailure) {
            return $this->blocked($result);
        }

        $request->session()->push(self::SESSION_KEY, $result->id);

        return response()->json([
            'status' => 'ok',
            'conversation' => $result->id,
            'max_messages' => (int) $this->settings->get('ai.max_messages_per_conversation', 20),
            'links' => $this->links(),
        ], 201);
    }

    public function message(Request $request, string $conversation, SendAssistantMessage $send): JsonResponse
    {
        $model = $this->conversation($request, $conversation);
        // La longueur est contrôlée par la garde (repli « message trop long ») : on borne seulement le volume reçu.
        $data = $request->validate(['message' => ['present', 'nullable', 'string', 'max:20000']]);

        $reply = $send->handle($model, (string) ($data['message'] ?? ''));

        if ($reply->failure !== null) {
            return $this->blocked($reply->failure);
        }

        /** @var Message $message */
        $message = $reply->message;
        $answer = $reply->answer;

        return response()->json([
            'status' => 'ok',
            'message' => [
                'id' => $message->id,
                'content' => $message->content,
                'grounding' => $answer?->grounding->value,
                'grounding_label' => $answer?->grounding->label(),
                'citations' => $this->citations($message->citations ?? []),
                'suggested_action' => $answer?->suggestedAction->value,
                'clarifying_question' => $answer?->clarifyingQuestion,
                'error_code' => $message->error_code,
            ],
            'whatsapp_text' => $answer?->suggestedAction === SuggestedAction::HandoffWhatsapp ? $this->whatsappSummary($model) : null,
            'proposal' => $reply->proposal !== null ? $this->proposal($reply->proposal) : null,
            'links' => $this->links(),
            'team' => $this->team(),
        ]);
    }

    public function revise(Request $request, string $proposal, ProposeAssistantAction $proposals): JsonResponse
    {
        $model = $this->ownedProposal($request, $proposal);
        $revised = $proposals->revise($model, (array) $request->input('payload', []));

        return response()->json(['status' => 'ok', 'proposal' => $this->proposal($revised)]);
    }

    public function confirm(Request $request, string $proposal, ConfirmAssistantAction $confirm): JsonResponse
    {
        $model = $this->ownedProposal($request, $proposal);
        $data = $request->validate(['hash' => ['required', 'string', 'size:64']]);

        $result = [...$confirm->handle($model, $data['hash'], $request->ip()), 'team' => $this->team()];

        return response()->json($result, $result['status'] === 'executed' ? 200 : 422);
    }

    public function cancel(Request $request, string $proposal, CancelAssistantAction $cancel): JsonResponse
    {
        $cancel->handle($this->ownedProposal($request, $proposal));

        return response()->json(['status' => 'cancelled', 'message' => 'Proposition annulée : rien n\'a été envoyé.']);
    }

    public function feedback(Request $request, string $conversation, int $message, RecordAssistantFeedback $feedback): JsonResponse
    {
        $model = $this->conversation($request, $conversation);
        $data = $request->validate([
            'rating' => ['required', Rule::enum(FeedbackRating::class)],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $target = Message::query()->where('conversation_id', $model->id)->where('role', MessageRole::Assistant)->findOrFail($message);
        $feedback->handle($target, FeedbackRating::from($data['rating']), $data['comment'] ?? null);

        return response()->json(['status' => 'ok', 'message' => 'Merci pour votre retour.']);
    }

    private function conversation(Request $request, string $id): Conversation
    {
        abort_unless(Str::isUlid($id) && in_array(Str::lower($id), array_map('strtolower', (array) $request->session()->get(self::SESSION_KEY, [])), true), 404);

        return Conversation::query()->findOrFail($id);
    }

    private function ownedProposal(Request $request, string $id): AiActionProposal
    {
        abort_unless(Str::isUlid($id), 404);
        $proposal = AiActionProposal::query()->findOrFail($id);
        $this->conversation($request, $proposal->conversation_id);

        return $proposal;
    }

    private function blocked(GuardFailure $failure): JsonResponse
    {
        return response()->json([
            'status' => $failure->unavailable ? 'unavailable' : 'notice',
            'reason' => $failure->reason,
            'message' => $failure->message,
            'fallback' => $this->links(),
            'team' => $this->team(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function proposal(AiActionProposal $proposal): array
    {
        return [
            'id' => $proposal->id,
            'type' => $proposal->type->value,
            'payload' => $proposal->payload,
            'hash' => $proposal->payload_hash,
            'expires_at' => $proposal->expires_at->toIso8601String(),
            'complete' => app(ProposeAssistantAction::class)->isComplete($proposal->type, $proposal->payload),
            'labels' => ProposeAssistantAction::BRIEF_LABELS,
        ];
    }

    /**
     * Seuls les liens internes au site sont rendus (TRD §7.6).
     *
     * @param  array<int, mixed>  $citations
     * @return list<array{url: string, title: string}>
     */
    private function citations(array $citations): array
    {
        $root = rtrim(url('/'), '/');

        return array_values(array_filter(array_map(fn (mixed $c): ?array => is_array($c) && is_string($c['url'] ?? null)
            && ($c['url'] === $root || str_starts_with($c['url'], $root.'/') || str_starts_with($c['url'], $root.'#'))
            ? ['url' => $c['url'], 'title' => (string) ($c['title'] ?? '')]
            : null, $citations)));
    }

    /**
     * Résumé (300 caractères max.) montré au visiteur avant l'ouverture de WhatsApp (TRD §7.5).
     */
    private function whatsappSummary(Conversation $conversation): string
    {
        $last = Message::query()->where('conversation_id', $conversation->id)->where('role', MessageRole::User)->orderByDesc('id')->value('content');

        return Str::limit('Bonjour, je viens de l\'assistant du site. '.trim((string) $last), 297);
    }

    /**
     * Disponibilité de l'équipe (IA-04) : les horaires viennent des paramètres, jamais du modèle ; ils ne
     * sont cités qu'une fois confirmés (sans « [À VALIDER] »).
     *
     * @return array{open: bool, hours: string|null}
     */
    private function team(): array
    {
        $hours = trim((string) $this->settings->get('contact.hours'));

        return [
            'open' => BusinessHours::isOpen(),
            'hours' => $hours !== '' && ! PendingValidation::has($hours) ? $hours : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function links(): array
    {
        $whatsapp = preg_replace('/\D+/', '', (string) $this->settings->get('contact.whatsapp_primary'));

        return [
            'diagnostic' => route('diagnostic.start'),
            'whatsapp' => 'https://wa.me/'.$whatsapp,
            'appointment' => route('appointment'),
            'contact' => route('contact'),
        ];
    }
}
