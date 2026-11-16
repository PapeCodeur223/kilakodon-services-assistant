<?php

return [

    // Interrupteur global (en plus du paramètre `ai.enabled` modifiable dans l'admin).
    'enabled' => (bool) env('AI_ENABLED', true),

    // fake : réponses déterministes (tests, développement) ; anthropic : API Anthropic (clé dans .env uniquement).
    'driver' => env('AI_DRIVER', 'fake'),

    'api_key' => env('ANTHROPIC_API_KEY'),

    'model' => env('ANTHROPIC_MODEL', 'claude-haiku-5-5'),

    'api_url' => 'https://api.anthropic.com/v1/messages',

    'api_version' => '2023-06-01',

    // TRD §7.3 : 700 jetons de réponse, 25 s, une seule nouvelle tentative (429 / 5xx) après 2 s.
    'max_tokens' => 700,
    'timeout' => 25,
    'retry_delay_ms' => 2000,

    // Version du prompt système (resources/prompts/assistant/system-{version}.md), enregistrée sur chaque message.
    'prompt_version' => 'v1',

    // Historique transmis au modèle.
    'history_messages' => 8,

    // Recherche (TRD §7.2).
    'retrieval' => [
        'max_chunks' => 6,
        'max_context_words' => 2500,
    ],

    // Gardes (TRD §7.4) ; le budget quotidien et le nombre de messages par conversation sont dans les paramètres.
    'guards' => [
        'max_message_chars' => 1000,
        'conversations_per_visitor_per_day' => 5,
        // Plafond par IP = 5 × 4 : IP partagées (opérateurs mobiles), la clé visiteur reste la limite principale.
        'ip_multiplier' => 4,
        'requests_per_minute_per_ip' => 6,
        'budget_alert_ratio' => 0.8,
    ],

    // Prix en dollars par million de jetons, par modèle, à renseigner depuis la page officielle des tarifs
    // (TRD §7.3). Modèle absent = « coût non configuré » : jamais de valeur inventée.
    // Exemple : 'claude-haiku-5-5' => ['input' => …, 'output' => …],
    'pricing' => [],

    // Mots vides français retirés des questions avant la recherche.
    'stopwords' => [
        'a', 'à', 'au', 'aux', 'avec', 'ce', 'ces', 'cet', 'cette', 'dans', 'de', 'des', 'du', 'elle', 'en', 'est',
        'et', 'il', 'ils', 'je', 'la', 'le', 'les', 'leur', 'leurs', 'lui', 'ma', 'mais', 'me', 'mes', 'moi', 'mon',
        'ne', 'nos', 'notre', 'nous', 'on', 'ou', 'où', 'par', 'pas', 'pour', 'qu', 'que', 'qui', 'sa', 'se', 'ses',
        'son', 'sont', 'sur', 'ta', 'te', 'tes', 'toi', 'ton', 'tu', 'un', 'une', 'vos', 'votre', 'vous', 'y',
        'est-ce', 'quoi', 'quel', 'quelle', 'quels', 'quelles', 'comment', 'combien', 'avez', 'faites', 'fait',
        'faire', 'peut', 'peux', 'pouvez', 'bonjour', 'svp', 'merci', 'slt', 'vs',
    ],

    // Synonymes métier (TRD §7.2) : un terme de la question ajoute ses équivalents à la recherche.
    'synonyms' => [
        'caisse' => ['point de vente', 'encaissement', 'ventes'],
        'erp' => ['logiciel de gestion', 'gestion intégrée'],
        'crm' => ['suivi clients', 'relances'],
        'stock' => ['inventaire', 'magasin', 'dépôt'],
        'facture' => ['facturation', 'devis'],
        'internet' => ['hors-ligne', 'connexion', 'réseau'],
        'ecole' => ['scolaire', 'lycée', 'élèves'],
        'boulangerie' => ['pâtisserie', 'production'],
        'appli' => ['application', 'mobile'],
        'livreurs' => ['terrain', 'équipes', 'mobile'],
        'prix' => ['coût', 'devis', 'tarif'],
        'cahier' => ['cahier des charges', 'méthode'],
        'mobile money' => ['orange money', 'wave', 'moov', 'sama'],
    ],

];
