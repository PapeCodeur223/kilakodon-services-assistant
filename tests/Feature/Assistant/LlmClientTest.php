<?php

use App\Domain\Assistant\Conversation\RespondTool;
use App\Domain\Assistant\Exceptions\LlmUnavailableException;
use App\Domain\Assistant\Llm\AnthropicClient;
use App\Domain\Assistant\Llm\FakeLlmClient;
use App\Domain\Assistant\Llm\LlmClient;
use App\Domain\Assistant\Llm\LlmRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function assistantRequest(string $question = 'La caisse marche sans Internet ?'): LlmRequest
{
    return new LlmRequest(
        system: "Prompt.\n<documents>\n<document id=\"7\" title=\"Caisse\" url=\"http://localhost/x\">\nLa caisse fonctionne hors-ligne.\n</document>\n</documents>",
        messages: [['role' => 'user', 'content' => $question]],
        maxTokens: 700,
        purpose: 'assistant',
        tool: RespondTool::definition(),
    );
}

beforeEach(function () {
    config(['assistant.api_key' => 'sk-test-factice', 'assistant.retry_delay_ms' => 0, 'assistant.model' => 'claude-haiku-5-5']);
});

it('choisit le client selon AI_DRIVER', function (string $driver, string $class) {
    config(['assistant.driver' => $driver]);

    expect(app(LlmClient::class))->toBeInstanceOf($class);
})->with([
    ['fake', FakeLlmClient::class],
    ['anthropic', AnthropicClient::class],
]);

it('impose l\'outil respond et lit sa sortie structurée', function () {
    Http::fake(['api.anthropic.com/*' => Http::response([
        'model' => 'claude-haiku-5-5',
        'content' => [['type' => 'tool_use', 'name' => 'respond', 'input' => ['answer' => 'Oui.', 'grounding' => 'sourced', 'citations' => [7], 'suggested_action' => 'none']]],
        'usage' => ['input_tokens' => 1200, 'output_tokens' => 80],
    ])]);

    $response = (new AnthropicClient)->complete(assistantRequest());

    expect($response->toolInput['answer'])->toBe('Oui.')
        ->and($response->inputTokens)->toBe(1200)
        ->and($response->outputTokens)->toBe(80);

    Http::assertSent(fn (Request $r): bool => $r->hasHeader('x-api-key', 'sk-test-factice')
        && $r->hasHeader('anthropic-version', '2023-06-01')
        && $r['tool_choice'] === ['type' => 'tool', 'name' => 'respond']
        && $r['max_tokens'] === 700);
});

it('refait une seule tentative après une erreur 5xx', function () {
    Http::fake(['api.anthropic.com/*' => Http::sequence()
        ->push(['error' => 'overloaded'], 529)
        ->push(['content' => [['type' => 'tool_use', 'input' => ['answer' => 'Oui.']]], 'usage' => []])]);

    expect((new AnthropicClient)->complete(assistantRequest())->toolInput)->toBe(['answer' => 'Oui.']);
    Http::assertSentCount(2);
});

it('lève LlmUnavailableException après deux échecs, sans clé ou sur une erreur client', function () {
    Http::fake(['api.anthropic.com/*' => Http::response(['error' => 'x'], 500)]);
    expect(fn () => (new AnthropicClient)->complete(assistantRequest()))->toThrow(LlmUnavailableException::class);
    Http::assertSentCount(2);

    config(['assistant.api_key' => null]);
    expect(fn () => (new AnthropicClient)->complete(assistantRequest()))->toThrow(LlmUnavailableException::class);
});

it('n\'envoie aucune requête réseau avec le client factice', function () {
    Http::fake();

    $response = (new FakeLlmClient)->complete(assistantRequest());

    expect($response->toolInput['grounding'])->toBe('sourced')
        ->and($response->toolInput['citations'])->toBe([7]);
    Http::assertNothingSent();
});

it('oriente le client factice vers le diagnostic sur une question de prix', function () {
    $response = (new FakeLlmClient)->complete(assistantRequest('Combien ça coûte ?'));

    expect($response->toolInput['suggested_action'])->toBe('start_diagnostic');
});
