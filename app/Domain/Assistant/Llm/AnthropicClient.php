<?php

namespace App\Domain\Assistant\Llm;

use App\Domain\Assistant\Exceptions\LlmUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * API Anthropic Messages (TRD §7.3) : sortie structurée imposée par `tool_choice`, timeout de 25 s,
 * une seule nouvelle tentative (429 ou 5xx) après 2 s. La clé vient uniquement de `.env` et n'est
 * jamais journalisée. Tout échec lève LlmUnavailableException : l'appelant affiche son repli.
 */
final class AnthropicClient implements LlmClient
{
    public function complete(LlmRequest $request): LlmResponse
    {
        $key = (string) config('assistant.api_key');
        if ($key === '') {
            throw new LlmUnavailableException('Clé API Anthropic absente.');
        }

        $model = (string) config('assistant.model');
        $body = [
            'model' => $model,
            'max_tokens' => $request->maxTokens,
            'system' => $request->system,
            'messages' => $request->messages,
        ];

        if ($request->tool !== null) {
            $body['tools'] = [$request->tool];
            $body['tool_choice'] = ['type' => 'tool', 'name' => $request->tool['name']];
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => (string) config('assistant.api_version'),
                'content-type' => 'application/json',
            ])
                ->timeout((int) config('assistant.timeout', 25))
                ->retry(2, (int) config('assistant.retry_delay_ms', 2000), fn (Throwable $e): bool => $e instanceof RequestException
                    && ($e->response->status() === 429 || $e->response->serverError()), throw: false)
                ->post((string) config('assistant.api_url'), $body);
        } catch (ConnectionException $e) {
            throw new LlmUnavailableException('Connexion à l\'API impossible : '.$e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            throw new LlmUnavailableException('API Anthropic : HTTP '.$response->status());
        }

        return $this->parse($response, $model);
    }

    private function parse(Response $response, string $model): LlmResponse
    {
        $data = (array) $response->json();
        $text = '';
        $toolInput = null;

        foreach ((array) ($data['content'] ?? []) as $block) {
            if (($block['type'] ?? null) === 'text') {
                $text .= (string) ($block['text'] ?? '');
            }
            if (($block['type'] ?? null) === 'tool_use' && is_array($block['input'] ?? null)) {
                $toolInput = $block['input'];
            }
        }

        return new LlmResponse(
            text: trim($text),
            model: (string) ($data['model'] ?? $model),
            inputTokens: (int) ($data['usage']['input_tokens'] ?? 0),
            outputTokens: (int) ($data['usage']['output_tokens'] ?? 0),
            toolInput: $toolInput,
        );
    }
}
