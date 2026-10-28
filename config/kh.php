<?php

return [

    // Développement local : secret MFA de admin@kilakodon.test restauré par le DevSeeder (jamais en production).
    'dev_admin_mfa_secret' => env('KH_DEV_ADMIN_MFA_SECRET'),

    // Fuseau d'affichage ; les dates restent stockées en UTC.
    'display_timezone' => env('KH_DISPLAY_TIMEZONE', 'Africa/Bamako'),

    // Paramètres par défaut (seedés, puis modifiables dans « Paramètres »).
    // Une information non confirmée par Kilakodon reste marquée [À VALIDER].
    'settings' => [
        'contact.whatsapp_primary' => env('KH_WHATSAPP_PRIMARY', '+22374666489'),
        'contact.whatsapp_secondary' => env('KH_WHATSAPP_SECONDARY', '+22397462839'),
        'contact.email' => env('KH_CONTACT_EMAIL', 'kilakodon@gmail.com'),
        'contact.city' => 'Bamako, Mali',
        'contact.hours' => 'Lundi–samedi, 8 h–18 h [À VALIDER]',
        'notify.lead_recipients' => [env('KH_CONTACT_EMAIL', 'kilakodon@gmail.com')],
        // Q-009 : IA coupée et budget nul tant que la direction n'a pas fixé le budget.
        'ai.enabled' => false,
        'ai.daily_budget_usd' => 0,
        'ai.max_messages_per_conversation' => 20,
        'consent.texts' => [
            'version' => 1,
            'processing' => 'J\'accepte que Kilakodon Services utilise ces informations pour répondre à ma demande.',
            'marketing' => 'Je souhaite recevoir occasionnellement des conseils et actualités de Kilakodon Services.',
        ],
    ],

];
