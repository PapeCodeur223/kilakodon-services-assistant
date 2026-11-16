<?php

namespace App\Domain\Assistant\Conversation;

/**
 * Réponses de repli fixes, jamais générées (AI_ASSISTANT §4, UX §4.3).
 */
final class FallbackTexts
{
    public const PRICE = 'Le coût dépend du périmètre (modules, nombre d\'utilisateurs, sites, intégrations). Le plus simple : faites le diagnostic gratuit (3 min), puis nous vous proposons un devis détaillé.';

    public const INVALID_OUTPUT = 'Je n\'ai pas pu formuler de réponse fiable. Voulez-vous parler à un conseiller ?';

    public const UNAVAILABLE = 'L\'assistant est momentanément indisponible. Vous pouvez faire le diagnostic ou nous écrire directement.';

    public const MESSAGE_LIMIT = 'Nous avons beaucoup échangé ! Pour aller plus loin, un conseiller peut reprendre la discussion.';

    public const TOO_LONG = 'Votre message est un peu long : pouvez-vous le résumer en quelques phrases ?';

    public const RATE_LIMITED = 'Vous écrivez un peu vite : patientez quelques secondes avant votre prochain message.';

    public const EMPTY = 'Votre message est vide : posez-moi votre question en quelques mots.';
}
