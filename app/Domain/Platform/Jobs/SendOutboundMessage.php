<?php

namespace App\Domain\Platform\Jobs;

use App\Domain\Platform\Enums\OutboundStatus;
use App\Domain\Platform\Models\OutboundMessage;
use App\Domain\Platform\Support\MailTemplates;
use App\Mail\KilakodonMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envoie un message de la file (COM-01). Idempotent : un message déjà envoyé ne part jamais deux fois.
 * Un échec est enregistré (`failed`, tentatives, dernière erreur) sans jamais toucher à l'action métier ;
 * l'équipe peut le renvoyer depuis l'écran « Envois ». Après 3 échecs : `abandoned`.
 */
class SendOutboundMessage implements ShouldQueue
{
    use Queueable;

    public const MAX_ATTEMPTS = 3;

    public int $tries = 1;

    public function __construct(public readonly int $messageId) {}

    public function handle(): void
    {
        // Verrou court : deux exécutions simultanées du même message n'envoient qu'une fois.
        Cache::lock('outbound:'.$this->messageId, 60)->get(fn () => $this->send());
    }

    private function send(): void
    {
        $message = OutboundMessage::query()->find($this->messageId);

        if ($message === null || ! in_array($message->status, [OutboundStatus::Queued, OutboundStatus::Failed], true)) {
            return;
        }

        try {
            Mail::to($message->to)->send(new KilakodonMail(MailTemplates::for($message)));

            $message->forceFill([
                'status' => OutboundStatus::Sent,
                'attempts' => $message->attempts + 1,
                'sent_at' => now(),
                'last_error' => null,
            ])->save();
        } catch (Throwable $e) {
            $attempts = $message->attempts + 1;

            $message->forceFill([
                'status' => $attempts >= self::MAX_ATTEMPTS ? OutboundStatus::Abandoned : OutboundStatus::Failed,
                'attempts' => $attempts,
                'last_error' => mb_substr($e::class.' : '.$e->getMessage(), 0, 1000),
            ])->save();

            report($e);
        }
    }
}
