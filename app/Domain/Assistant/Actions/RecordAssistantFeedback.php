<?php

namespace App\Domain\Assistant\Actions;

use App\Domain\Assistant\Enums\FeedbackRating;
use App\Domain\Assistant\Enums\MessageRole;
use App\Domain\Assistant\Enums\ReviewStatus;
use App\Domain\Assistant\Models\AiFeedback;
use App\Domain\Assistant\Models\Message;
use InvalidArgumentException;

/**
 * Retour du visiteur sur une réponse (IA-06) : un 👎 crée une entrée « à revoir » pour l'éditeur.
 * Le visiteur peut changer d'avis tant que l'équipe n'a pas traité l'entrée ; ensuite, son statut ne bouge plus.
 */
class RecordAssistantFeedback
{
    public function handle(Message $message, FeedbackRating $rating, ?string $comment = null): AiFeedback
    {
        if ($message->role !== MessageRole::Assistant) {
            throw new InvalidArgumentException('Seules les réponses de l\'assistant reçoivent un retour.');
        }

        $comment = $comment !== null && trim($comment) !== '' ? mb_substr(trim($comment), 0, 500) : null;
        $feedback = AiFeedback::query()->firstOrNew(['message_id' => $message->id]);
        $alreadyHandled = $feedback->exists && $feedback->review_status !== ReviewStatus::ToReview;

        $feedback->fill([
            'rating' => $rating,
            'comment' => $comment,
            'review_status' => match (true) {
                $alreadyHandled => $feedback->review_status,
                $rating === FeedbackRating::Down => ReviewStatus::ToReview,
                default => ReviewStatus::Reviewed,
            },
        ])->save();

        return $feedback;
    }
}
