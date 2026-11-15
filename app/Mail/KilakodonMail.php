<?php

namespace App\Mail;

use App\Domain\Platform\Support\MailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * E-mail Markdown à l'identité Kilakodon, construit depuis un modèle de la file d'envoi.
 *
 * @phpstan-import-type MailContent from MailTemplates
 */
class KilakodonMail extends Mailable
{
    use Queueable;

    /**
     * @param  MailContent  $content
     */
    public function __construct(public readonly array $content) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->content['subject']);
    }

    public function content(): Content
    {
        return new Content(markdown: $this->content['view'], with: $this->content['data']);
    }
}
