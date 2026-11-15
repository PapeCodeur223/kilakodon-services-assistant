<?php

namespace App\Domain\Platform\Actions;

use App\Domain\Platform\Enums\OutboundChannel;
use App\Domain\Platform\Enums\OutboundStatus;
use App\Domain\Platform\Jobs\SendOutboundMessage;
use App\Domain\Platform\Models\OutboundMessage;
use Illuminate\Database\Eloquent\Model;

/**
 * Met un e-mail en file (`outbound_messages`) puis demande son envoi après validation de la transaction.
 * L'action métier est enregistrée d'abord : un échec d'envoi ne peut jamais la faire perdre (TRD §12).
 */
class QueueOutboundMessage
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $to, string $template, array $payload, ?Model $related = null): OutboundMessage
    {
        $message = new OutboundMessage([
            'channel' => OutboundChannel::Email,
            'to' => $to,
            'template' => $template,
            'payload' => $payload,
            'status' => OutboundStatus::Queued,
        ]);

        if ($related !== null) {
            $message->related()->associate($related);
        }

        $message->save();

        SendOutboundMessage::dispatch($message->id)->afterCommit();

        return $message;
    }
}
