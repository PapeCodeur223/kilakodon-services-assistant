<?php

namespace App\Domain\Assistant\Conversation;

use App\Domain\Assistant\Models\AiActionProposal;
use App\Domain\Assistant\Models\Message;

/**
 * Résultat d'un envoi : soit une garde a bloqué (aucun appel au modèle), soit un message assistant
 * enregistré (éventuellement un repli) et, le cas échéant, la proposition d'action à confirmer.
 */
final readonly class AssistantReply
{
    private function __construct(
        public ?GuardFailure $failure,
        public ?Message $message,
        public ?AssistantAnswer $answer,
        public ?AiActionProposal $proposal,
    ) {}

    public static function blocked(GuardFailure $failure): self
    {
        return new self($failure, null, null, null);
    }

    public static function answered(Message $message, AssistantAnswer $answer, ?AiActionProposal $proposal = null): self
    {
        return new self(null, $message, $answer, $proposal);
    }
}
