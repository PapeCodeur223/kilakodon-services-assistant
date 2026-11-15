<?php

use App\Domain\Crm\Actions\AlertUnattendedLeads;
use App\Domain\Crm\Actions\CreateLeadFromContact;
use App\Domain\Crm\Data\ContactRequest;
use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Support\BusinessHours;
use App\Domain\Platform\Actions\QueueOutboundMessage;
use App\Domain\Platform\Actions\ResendOutboundMessage;
use App\Domain\Platform\Enums\OutboundStatus;
use App\Domain\Platform\Jobs\SendOutboundMessage;
use App\Domain\Platform\Models\OutboundMessage;
use App\Domain\Platform\Models\User;
use App\Domain\Platform\Services\Settings;
use App\Domain\Platform\Support\MailTemplates;
use App\Mail\KilakodonMail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

it('envoie les e-mails de la file et les marque envoyés, une seule fois', function () {
    Mail::fake();
    app(Settings::class)->set('notify.lead_recipients', ['equipe@example.test']);

    app(CreateLeadFromContact::class)->handle(new ContactRequest('Aminata Coulibaly', '74 66 64 89', 'aminata@example.test', 'project', 'Logiciel de caisse.', false, '1.1.1.1'));

    expect(OutboundMessage::query()->where('status', OutboundStatus::Sent)->count())->toBe(2);
    Mail::assertSent(KilakodonMail::class, 2);

    // Rejouer le job d'un message déjà envoyé ne renvoie rien.
    (new SendOutboundMessage(OutboundMessage::query()->firstOrFail()->id))->handle();
    Mail::assertSent(KilakodonMail::class, 2);
});

/**
 * Transport d'e-mail qui échoue comme un serveur SMTP injoignable.
 */
function useFailingMailer(string $error = 'Connexion SMTP refusée'): void
{
    Mail::extend('failing', fn () => new class($error) extends AbstractTransport
    {
        public function __construct(private readonly string $error)
        {
            parent::__construct();
        }

        protected function doSend(SentMessage $message): void
        {
            throw new TransportException($this->error);
        }

        public function __toString(): string
        {
            return 'failing://';
        }
    });
    config(['mail.mailers.failing' => ['transport' => 'failing'], 'mail.default' => 'failing']);
    Mail::purge('failing');
}

it('laisse le lead intact et l\'envoi en « échec » relançable quand le SMTP échoue (COM-01)', function () {
    useFailingMailer();
    app(Settings::class)->set('notify.lead_recipients', ['equipe@example.test']);

    $lead = app(CreateLeadFromContact::class)->handle(new ContactRequest('Aminata Coulibaly', '74 66 64 89', null, 'project', 'Logiciel de caisse.', false, '1.1.1.1'));

    $message = OutboundMessage::query()->sole();
    expect(Lead::query()->find($lead->id))->not->toBeNull()
        ->and($message->status)->toBe(OutboundStatus::Failed)
        ->and($message->attempts)->toBe(1)
        ->and($message->last_error)->toContain('Connexion SMTP refusée');

    // Le serveur répond de nouveau : « Renvoyer » envoie le message sans rejouer la demande.
    config(['mail.default' => 'array']);
    Mail::purge();
    app(ResendOutboundMessage::class)->handle($message);

    expect($message->refresh()->status)->toBe(OutboundStatus::Sent)
        ->and($message->sent_at)->not->toBeNull();
});

it('abandonne après 3 échecs, mais reste relançable à la main', function () {
    useFailingMailer('SMTP indisponible');

    $message = app(QueueOutboundMessage::class)->handle('equipe@example.test', 'lead_new_internal', ['lead_id' => 1, 'type' => 'contact']);
    (new SendOutboundMessage($message->id))->handle();
    (new SendOutboundMessage($message->id))->handle();

    expect($message->refresh()->status)->toBe(OutboundStatus::Abandoned)
        ->and($message->attempts)->toBe(3);

    expect(fn () => app(ResendOutboundMessage::class)->handle(OutboundMessage::query()->create(['channel' => 'email', 'to' => 'x@example.test', 'template' => 'lead_ack', 'payload' => [], 'status' => 'sent'])))
        ->toThrow(InvalidArgumentException::class);
});

it('n\'écrit aucune coordonnée dans l\'objet des e-mails et rend chaque modèle', function (string $template, array $payload) {
    $message = new OutboundMessage(['channel' => 'email', 'to' => 'x@example.test', 'template' => $template, 'payload' => $payload, 'status' => 'queued']);
    $content = MailTemplates::for($message);
    $html = (new KilakodonMail($content))->render();

    expect($content['subject'])->not->toContain('Awa')->not->toContain('+223')->not->toContain('@')
        ->and($html)->toContain('Kilakodon');
})->with([
    'nouveau lead' => ['lead_new_internal', ['lead_id' => 7, 'type' => 'diagnostic', 'reference' => 'KLK-2026-0007']],
    'accusé contact' => ['lead_ack', ['lead_id' => 7, 'first_name' => 'Awa']],
    'accusé brief' => ['brief_ack', ['lead_id' => 7, 'first_name' => 'Awa', 'reference' => 'KLK-2026-0007']],
    'accusé RDV' => ['appointment_ack', ['lead_id' => 7, 'first_name' => 'Awa', 'topic' => 'Caisse']],
    'RDV demandé (équipe)' => ['appointment_requested_internal', ['lead_id' => 7]],
    'RDV confirmé' => ['appointment_confirmed', ['first_name' => 'Awa', 'date' => 'lundi 3 novembre 2026', 'time' => '10:30', 'channel' => 'Appel WhatsApp', 'topic' => 'Caisse']],
    'leads en attente' => ['lead_unattended', ['lead_ids' => [3, 4]]],
]);

it('ne dit jamais « confirmé » dans l\'accusé de demande de rendez-vous (RDV-01)', function () {
    $message = new OutboundMessage(['channel' => 'email', 'to' => 'x@example.test', 'template' => 'appointment_ack', 'payload' => ['first_name' => 'Awa'], 'status' => 'queued']);
    $html = (new KilakodonMail(MailTemplates::for($message)))->render();

    expect($html)->toContain('pas encore confirmé')->not->toContain('Votre rendez-vous est confirmé');
});

it('compte les heures ouvrées (lundi–samedi, 8 h–18 h, Bamako)', function () {
    $tz = 'Africa/Bamako';

    expect(BusinessHours::between(Carbon::parse('2026-11-02 09:00', $tz), Carbon::parse('2026-11-02 11:30', $tz)))->toBe(2.5)
        ->and(BusinessHours::between(Carbon::parse('2026-11-02 17:00', $tz), Carbon::parse('2026-11-03 09:00', $tz)))->toBe(2.0)
        // Samedi 17 h → lundi 9 h : 1 h le samedi, rien le dimanche, 1 h le lundi.
        ->and(BusinessHours::between(Carbon::parse('2026-11-07 17:00', $tz), Carbon::parse('2026-11-09 09:00', $tz)))->toBe(2.0);
});

it('alerte les administrateurs une seule fois par lead non traité depuis 24 h ouvrées', function () {
    Mail::fake();
    User::factory()->admin()->create(['email' => 'direction@example.test']);
    Carbon::setTestNow(Carbon::parse('2026-11-05 10:00', 'Africa/Bamako'));

    $old = makeLead(['created_at' => now()->subDays(3)]);
    $answered = makeLead(['created_at' => now()->subDays(3), 'first_response_at' => now()->subDays(2)]);
    $recent = makeLead(['created_at' => now()->subHours(5)]);
    $closed = makeLead(['created_at' => now()->subDays(3), 'stage' => LeadStage::Lost]);

    expect(app(AlertUnattendedLeads::class)->handle())->toBe([$old->id])
        ->and(OutboundMessage::query()->where('template', 'lead_unattended')->sole()->to)->toBe('direction@example.test');

    // Deuxième passage : rien de nouveau.
    expect(app(AlertUnattendedLeads::class)->handle())->toBe([]);
    $this->artisan('kh:leads-unattended')->assertSuccessful();

    expect([$answered->id, $recent->id, $closed->id])->each->not->toBeIn([$old->id]);
    Carbon::setTestNow();
});
