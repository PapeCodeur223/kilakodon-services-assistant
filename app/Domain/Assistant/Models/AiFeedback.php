<?php

namespace App\Domain\Assistant\Models;

use App\Domain\Assistant\Enums\FeedbackRating;
use App\Domain\Assistant\Enums\ReviewStatus;
use App\Domain\Platform\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Retour 👍/👎 sur une réponse (IA-06). Un 👎 crée une entrée « à revoir ».
 *
 * @property int $id
 * @property int $message_id
 * @property FeedbackRating $rating
 * @property string|null $comment
 * @property ReviewStatus $review_status
 * @property int|null $reviewed_by
 * @property string|null $review_note
 * @property-read Message $message
 * @property-read User|null $reviewer
 */
class AiFeedback extends Model
{
    protected $table = 'ai_feedback';

    protected $fillable = ['message_id', 'rating', 'comment', 'review_status', 'reviewed_by', 'review_note'];

    protected function casts(): array
    {
        return [
            'rating' => FeedbackRating::class,
            'review_status' => ReviewStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Message, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
