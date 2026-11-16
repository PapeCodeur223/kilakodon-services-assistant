<?php

namespace App\Domain\Assistant\Conversation;

/**
 * Blocage d'une garde : code (journalisé, renvoyé à l'API) et texte de repli fixe affiché au visiteur.
 * `unavailable` : l'assistant ne peut pas répondre (le widget propose diagnostic, WhatsApp, contact) ;
 * sinon, simple remarque sur le message (vide, trop long) : la conversation continue.
 */
final readonly class GuardFailure
{
    private function __construct(
        public string $reason,
        public string $message,
        public bool $unavailable,
    ) {}

    public static function disabled(): self
    {
        return new self('disabled', FallbackTexts::UNAVAILABLE, true);
    }

    public static function budget(): self
    {
        return new self('budget', FallbackTexts::UNAVAILABLE, true);
    }

    public static function visitorLimit(): self
    {
        return new self('visitor_limit', FallbackTexts::MESSAGE_LIMIT, true);
    }

    public static function messageLimit(): self
    {
        return new self('message_limit', FallbackTexts::MESSAGE_LIMIT, true);
    }

    public static function rateLimited(): self
    {
        return new self('rate_limited', FallbackTexts::RATE_LIMITED, false);
    }

    public static function empty(): self
    {
        return new self('empty', FallbackTexts::EMPTY, false);
    }

    public static function tooLong(): self
    {
        return new self('too_long', FallbackTexts::TOO_LONG, false);
    }
}
