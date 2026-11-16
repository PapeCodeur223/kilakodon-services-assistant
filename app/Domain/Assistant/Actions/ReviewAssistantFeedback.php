<?php

namespace App\Domain\Assistant\Actions;

use App\Domain\Assistant\Enums\ReviewStatus;
use App\Domain\Assistant\Models\AiFeedback;
use App\Domain\Platform\Models\User;

/**
 * Revue d'un retour (AI_ASSISTANT §6) : « revu » (rien à changer) ou « traité » (contenu ou FAQ
 * enrichi, puis base reconstruite). Jamais d'ajout automatique de conversation dans la base.
 */
class ReviewAssistantFeedback
{
    public function handle(AiFeedback $feedback, ReviewStatus $status, User $by, ?string $note = null): AiFeedback
    {
        $feedback->forceFill([
            'review_status' => $status,
            'reviewed_by' => $by->id,
            'review_note' => $note !== null && trim($note) !== '' ? trim($note) : null,
        ])->save();

        return $feedback;
    }
}
