<?php

namespace App\Domain\Crm\Support;

use App\Domain\Assistant\Models\Conversation;
use App\Domain\Brief\Models\Brief;
use App\Domain\Crm\Models\Appointment;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Lead;
use App\Domain\Platform\Models\OutboundMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Périmètre des données d'un contact (PRV-02), partagé par l'export et l'anonymisation :
 * leads (y compris supprimés), briefs, conversations et e-mails liés.
 */
final class ContactDataScope
{
    /**
     * @param  Collection<int, Lead>  $leads
     * @param  Collection<int, Brief>  $briefs
     * @param  Collection<int, Conversation>  $conversations
     * @param  Collection<int, OutboundMessage>  $outbound
     */
    private function __construct(
        public readonly Collection $leads,
        public readonly Collection $briefs,
        public readonly Collection $conversations,
        public readonly Collection $outbound,
    ) {}

    public static function for(Contact $contact): self
    {
        $leads = Lead::withTrashed()->with(['activities', 'appointments'])->where('contact_id', $contact->id)->orderBy('id')->get();
        $leadIds = $leads->pluck('id')->all();

        $briefs = Brief::query()->with('versions')
            ->where(fn (Builder $query) => $query->where('contact_id', $contact->id)->orWhereIn('id', $leads->pluck('brief_id')->filter()->all()))
            ->orderBy('id')
            ->get();

        $conversations = Conversation::query()->with('messages')
            ->where(fn (Builder $query) => $query->whereIn('lead_id', $leadIds ?: [0])->orWhereIn('id', $briefs->pluck('conversation_id')->filter()->all()))
            ->orderBy('created_at')
            ->get();

        $addresses = array_values(array_filter([$contact->email, $contact->phone_e164, $contact->whatsapp_e164]));
        $appointmentIds = Appointment::query()->whereIn('lead_id', $leadIds ?: [0])->pluck('id')->all();

        $outbound = OutboundMessage::query()
            ->where(function (Builder $query) use ($addresses, $leadIds, $appointmentIds, $briefs): void {
                $query->whereIn('to', $addresses ?: [''])
                    ->orWhere(fn (Builder $q) => $q->where('related_type', (new Lead)->getMorphClass())->whereIn('related_id', $leadIds ?: [0]))
                    ->orWhere(fn (Builder $q) => $q->where('related_type', (new Appointment)->getMorphClass())->whereIn('related_id', $appointmentIds ?: [0]))
                    ->orWhere(fn (Builder $q) => $q->where('related_type', (new Brief)->getMorphClass())->whereIn('related_id', $briefs->pluck('id')->all() ?: [0]));
            })
            ->orderBy('id')
            ->get();

        return new self($leads, $briefs, $conversations, $outbound);
    }
}
