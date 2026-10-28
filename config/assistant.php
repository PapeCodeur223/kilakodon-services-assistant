<?php

return [

    // Interrupteur global (en plus du paramètre `ai.enabled` modifiable dans l'admin).
    'enabled' => (bool) env('AI_ENABLED', true),

    // fake : réponses déterministes (tests, développement) ; anthropic : client HTTP (lot 4).
    'driver' => env('AI_DRIVER', 'fake'),

    'api_key' => env('ANTHROPIC_API_KEY'),

    'model' => env('ANTHROPIC_MODEL', 'claude-haiku-5-5'),

    // Prix par million de jetons, à renseigner depuis la page officielle des tarifs (TRD §7.3).
    // null = « coût non configuré » ; ne jamais inventer de valeur.
    'pricing' => [],

];
