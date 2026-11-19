<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Assistant\Models\Conversation;
use App\Domain\Brief\Models\Brief;
use App\Domain\Brief\Models\BriefVersion;
use App\Domain\Crm\Models\Activity;
use App\Domain\Crm\Models\Appointment;
use App\Domain\Crm\Models\Consent;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Support\ContactDataScope;
use App\Domain\Diagnostic\Models\DiagnosticAnswer;
use App\Domain\Platform\Actions\RecordAudit;
use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\OutboundMessage;
use App\Domain\Platform\Models\User;
use Illuminate\Support\Collection;

/**
 * Droit d'accès (PRV-02) : toutes les données personnelles d'un contact, en JSON lisible.
 * Jamais de secret technique (jetons, empreintes d'IP). L'export est journalisé.
 */
class ExportContactData
{
    public function __construct(private readonly RecordAudit $audit) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(Contact $contact, User $by): array
    {
        $scope = ContactDataScope::for($contact);
        $tz = (string) config('kh.display_timezone');
        $date = fn ($value): ?string => $value?->timezone($tz)->toIso8601String();

        $export = [
            'generated_at' => now($tz)->toIso8601String(),
            'controller' => 'Kilakodon Services — Bamako, Mali',
            'contact' => [
                'full_name' => $contact->full_name,
                'phone' => $contact->phone_e164,
                'whatsapp' => $contact->whatsapp_e164,
                'email' => $contact->email,
                'job_title' => $contact->job_title,
                'city' => $contact->city,
                'country' => $contact->country_code,
                'company' => $contact->company?->name,
                'preferred_channel' => $contact->preferred_channel,
                'marketing_opt_in' => $contact->marketing_opt_in,
                'created_at' => $date($contact->created_at),
            ],
            'consents' => Consent::query()->where('contact_id', $contact->id)->orderBy('id')->get()
                ->map(fn (Consent $consent): array => [
                    'purpose' => $consent->purpose->label(),
                    'granted' => $consent->granted,
                    'text_version' => $consent->text_version,
                    'source' => $consent->source->label(),
                    'at' => $date($consent->created_at),
                ])->all(),
            'leads' => $scope->leads->map(fn (Lead $lead): array => [
                'type' => $lead->type->label(),
                'source' => $lead->source,
                'stage' => $lead->stage->label(),
                'message' => $lead->message,
                'created_at' => $date($lead->created_at),
                'activities' => $lead->activities->map(fn (Activity $activity): array => [
                    'type' => $activity->type->label(),
                    'body' => $activity->body,
                    'at' => $date($activity->created_at),
                ])->all(),
                'appointments' => $lead->appointments->map(fn (Appointment $appointment): array => [
                    'topic' => $appointment->topic,
                    'channel' => $appointment->channel->label(),
                    'status' => $appointment->status->label(),
                    'scheduled_at' => $date($appointment->scheduled_at),
                ])->all(),
            ])->all(),
            'briefs' => $scope->briefs->map(fn (Brief $brief): array => [
                'reference' => $brief->reference,
                'status' => $brief->status->label(),
                'validated_at' => $date($brief->validated_at),
                'versions' => $brief->versions->map(fn (BriefVersion $version): array => [
                    'version' => $version->version,
                    'declared' => $version->declared,
                    'summary' => $version->summary,
                    'comment' => $version->prospect_comment,
                    'at' => $date($version->created_at),
                ])->all(),
                'diagnostic_answers' => $brief->diagnostic_session_id !== null
                    ? DiagnosticAnswer::query()->where('session_id', $brief->diagnostic_session_id)->pluck('value', 'question_key')->all()
                    : [],
            ])->all(),
            'conversations' => $scope->conversations->map(fn (Conversation $conversation): array => [
                'started_at' => $date($conversation->created_at),
                'messages' => $conversation->messages->map(fn ($message): array => [
                    'role' => $message->role->label(),
                    'content' => $message->content,
                    'at' => $date($message->created_at),
                ])->all(),
            ])->all(),
            'messages_sent' => $this->outbound($scope->outbound),
        ];

        $this->audit->handle(AuditAction::Exported, $contact, ['after' => ['format' => 'json', 'leads' => $scope->leads->count()]], $by);

        return $export;
    }

    /**
     * @param  Collection<int, OutboundMessage>  $messages
     * @return list<array{channel: string, template: string, status: string, sent_at: string|null}>
     */
    private function outbound(Collection $messages): array
    {
        return $messages->map(fn (OutboundMessage $message): array => [
            'channel' => $message->channel->label(),
            'template' => $message->template,
            'status' => $message->status->label(),
            'sent_at' => $message->sent_at?->toIso8601String(),
        ])->values()->all();
    }
}
