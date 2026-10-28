<?php

namespace App\Domain\Assistant\Llm;

use App\Domain\Assistant\Exceptions\LlmUnavailableException;

/**
 * Accès au modèle de langage (TRD §7.3). Le lot 2 n'en utilise que la génération de texte simple
 * (reformulation du diagnostic) ; la sortie structurée de l'assistant arrive au lot 4.
 */
interface LlmClient
{
    /**
     * @throws LlmUnavailableException
     */
    public function complete(LlmRequest $request): LlmResponse;
}
