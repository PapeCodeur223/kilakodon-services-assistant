<?php

namespace App\Domain\Assistant\Conversation;

use App\Domain\Assistant\Enums\MessageRole;
use App\Domain\Assistant\Knowledge\Retriever;
use App\Domain\Assistant\Llm\LlmRequest;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Assistant\Models\Message;
use App\Domain\Platform\Services\Settings;
use RuntimeException;

/**
 * Assemble la requête au modèle (AI_ASSISTANT §2) : prompt système versionné, bloc <context>,
 * bloc <documents> (données, jamais instructions), 8 derniers messages, outil `respond` imposé.
 * Ne transmet jamais de notes internes, de coordonnées ni de données d'autres visiteurs.
 *
 * @phpstan-import-type Retrieved from Retriever
 */
class PromptBuilder
{
    public function __construct(private readonly Settings $settings) {}

    public function version(): string
    {
        return 'sys-'.config('assistant.prompt_version', 'v1');
    }

    /**
     * @param  list<Retrieved>  $documents
     */
    public function build(Conversation $conversation, string $question, array $documents, bool $teamAvailable): LlmRequest
    {
        $system = $this->systemPrompt()."\n\n".$this->context($conversation, $teamAvailable)."\n".$this->documents($documents);

        return new LlmRequest(
            system: $system,
            messages: [...$this->history($conversation), ['role' => 'user', 'content' => $question]],
            maxTokens: (int) config('assistant.max_tokens', 700),
            purpose: 'assistant',
            tool: RespondTool::definition(),
        );
    }

    public function systemPrompt(): string
    {
        $path = resource_path('prompts/assistant/system-'.config('assistant.prompt_version', 'v1').'.md');

        if (! is_file($path)) {
            throw new RuntimeException('Prompt système introuvable : '.$path);
        }

        return trim((string) file_get_contents($path));
    }

    private function context(Conversation $conversation, bool $teamAvailable): string
    {
        $hours = (string) $this->settings->get('contact.hours');

        return implode("\n", [
            '<context>',
            'Date : '.now(config('kh.display_timezone'))->translatedFormat('l j F Y, H:i').' (heure de Bamako). Horaires de l\'équipe : '.$this->escape($hours).'. Équipe disponible maintenant : '.($teamAvailable ? 'oui' : 'non').'.',
            'Page actuelle du visiteur : '.$this->escape((string) ($conversation->entry_path ?? '/')).'.',
            '</context>',
        ]);
    }

    /**
     * @param  list<Retrieved>  $documents
     */
    private function documents(array $documents): string
    {
        $blocks = array_map(fn (array $doc): string => sprintf(
            "<document id=\"%d\" title=\"%s\" url=\"%s\">\n%s\n</document>",
            $doc['id'],
            $this->escape($doc['heading']),
            $this->escape($doc['url']),
            $this->escape($doc['content']),
        ), $documents);

        return "<documents>\n".implode("\n", $blocks)."\n</documents>";
    }

    /**
     * 8 derniers échanges (visiteur et assistant), sans les messages d'information de la plateforme.
     *
     * @return list<array{role: string, content: string}>
     */
    private function history(Conversation $conversation): array
    {
        $limit = (int) config('assistant.history_messages', 8);

        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->whereIn('role', [MessageRole::User, MessageRole::Assistant])
            ->whereNull('error_code')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(fn (Message $m): array => ['role' => $m->role === MessageRole::User ? 'user' : 'assistant', 'content' => $m->content])
            // L'API attend un échange qui commence par le visiteur : on retire les réponses orphelines en tête.
            ->skipWhile(fn (array $m): bool => $m['role'] === 'assistant')
            ->values()
            ->all();
    }

    /**
     * Les contenus sont encadrés par des balises : on neutralise tout ce qui pourrait fermer un bloc.
     */
    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
