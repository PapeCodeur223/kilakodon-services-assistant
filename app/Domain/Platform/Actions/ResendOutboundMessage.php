<?php

namespace App\Domain\Platform\Actions;

use App\Domain\Platform\Enums\OutboundStatus;
use App\Domain\Platform\Jobs\SendOutboundMessage;
use App\Domain\Platform\Models\OutboundMessage;
use InvalidArgumentException;

/**
 * « Renvoyer » depuis l'écran Envois : relance un message en échec ou abandonné, sans rejouer
 * l'action métier qui l'a produit (TRD §12).
 */
class ResendOutboundMessage
{
    public function handle(OutboundMessage $message): OutboundMessage
    {
        if (! in_array($message->status, [OutboundStatus::Failed, OutboundStatus::Abandoned], true)) {
            throw new InvalidArgumentException('Seul un envoi en échec ou abandonné peut être renvoyé.');
        }

        $message->forceFill(['status' => OutboundStatus::Queued, 'attempts' => 0])->save();
        SendOutboundMessage::dispatch($message->id);

        return $message;
    }
}
