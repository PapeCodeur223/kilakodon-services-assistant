<?php

namespace App\Domain\Assistant\Llm;

use App\Domain\Assistant\Exceptions\LlmUnavailableException;

/**
 * Pilote utilisé tant qu'aucun client réel n'est branché (le client Anthropic arrive au lot 4) :
 * chaque appel échoue proprement et l'appelant utilise son repli.
 */
final class UnavailableLlmClient implements LlmClient
{
    public function complete(LlmRequest $request): LlmResponse
    {
        throw new LlmUnavailableException('Aucun client IA disponible.');
    }
}
