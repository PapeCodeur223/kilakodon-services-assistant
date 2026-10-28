<?php

use App\Domain\Crm\Actions\RequestAppointment;
use App\Domain\Crm\Data\AppointmentRequest;
use App\Domain\Crm\Enums\AppointmentChannel;
use App\Domain\Crm\Enums\AppointmentStatus;
use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Crm\Models\Contact;
use App\Domain\Platform\Enums\OutboundStatus;
use App\Domain\Platform\Models\OutboundMessage;
use App\Domain\Platform\Models\User;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Resources\Contacts\Pages\ViewContact;
use App\Filament\Resources\Leads\Pages\LeadsKanban;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Filament\Resources\OutboundMessages\Pages\ListOutboundMessages;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

it('ouvre le CRM au commercial et à l\'admin, jamais à l\'éditeur', function (string $path) {
    $this->actingAs(User::factory()->sales()->create())->get($path)->assertOk();
    $this->actingAs(User::factory()->editor()->create())->get($path)->assertForbidden();
})->with(['/admin/leads', '/admin/leads/kanban', '/admin/contacts', '/admin/companies', '/admin/appointments']);

it('réserve l\'écran « Envois » à l\'admin ; un commercial ne modifie pas les paramètres', function () {
    $sales = User::factory()->sales()->create();

    $this->actingAs($sales)->get('/admin/outbound-messages')->assertForbidden();
    $this->actingAs($sales)->get('/admin/parametres')->assertForbidden();
    expect($sales->can('manage-settings'))->toBeFalse();

    $this->actingAs(User::factory()->admin()->withMfa()->create())->get('/admin/outbound-messages')->assertOk();
});

it('affiche la fiche lead complète et change l\'étape avec un motif obligatoire pour « perdu »', function () {
    $sales = User::factory()->sales()->create();
    $lead = makeLead([], ['full_name' => 'Moussa Diallo']);
    $this->actingAs($sales);

    $this->get('/admin/leads/'.$lead->id)->assertOk()
        ->assertSee('Moussa Diallo')
        ->assertSee('Qualification')
        ->assertSee('Aucune — à traiter');

    Livewire::test(ViewLead::class, ['record' => $lead->getRouteKey()])
        ->callAction('changeStage', ['stage' => LeadStage::Lost->value])
        ->assertHasActionErrors(['lost_reason' => 'required']);

    Livewire::test(ViewLead::class, ['record' => $lead->getRouteKey()])
        ->callAction('changeStage', ['stage' => LeadStage::Qualified->value])
        ->assertHasNoActionErrors()
        ->assertNotified('Étape mise à jour');

    expect($lead->refresh()->stage)->toBe(LeadStage::Qualified);

    Livewire::test(ViewLead::class, ['record' => $lead->getRouteKey()])
        ->callAction('logActivity', ['type' => 'note', 'body' => 'Client intéressé par la caisse.'])
        ->assertNotified('Activité enregistrée');

    expect($lead->activities()->where('type', 'note')->value('body'))->toBe('Client intéressé par la caisse.');
});

it('déplace un lead dans le Kanban et demande un motif pour la mise en pause', function () {
    $this->actingAs(User::factory()->sales()->create());
    $lead = makeLead();

    Livewire::test(LeadsKanban::class)
        ->assertSee($lead->contact->full_name)
        ->call('moveLead', $lead->id, LeadStage::Qualified->value)
        ->assertNotified();
    expect($lead->refresh()->stage)->toBe(LeadStage::Qualified);

    // Transition interdite : refusée par l'Action, l'étape ne bouge pas.
    Livewire::test(LeadsKanban::class)->call('moveLead', $lead->id, LeadStage::Won->value);
    expect($lead->refresh()->stage)->toBe(LeadStage::Qualified);

    Livewire::test(LeadsKanban::class)
        ->call('moveLead', $lead->id, LeadStage::OnHold->value)
        ->assertActionMounted('moveWithReason')
        ->fillForm(['reason' => 'Budget voté en janvier'])
        ->callMountedAction()
        ->assertHasNoActionErrors();
    expect($lead->refresh()->stage)->toBe(LeadStage::OnHold);
});

it('confirme un rendez-vous depuis l\'admin', function () {
    $sales = User::factory()->sales()->create();
    $appointment = app(RequestAppointment::class)->handle(new AppointmentRequest(
        'Ibrahim Sangaré', '76 12 34 56', 'ibrahim@example.test', null, 'Démo caisse', AppointmentChannel::Video,
        [['date' => now()->addDays(2)->toDateString(), 'period' => 'morning']],
    ));
    $this->actingAs($sales);

    Livewire::test(ListAppointments::class)
        ->callTableAction('confirm', $appointment, ['scheduled_at' => Carbon::now('Africa/Bamako')->addDays(2)->setTime(10, 0)->format('Y-m-d H:i:s')])
        ->assertHasNoTableActionErrors()
        ->assertNotified('Rendez-vous confirmé');

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Confirmed)
        ->and($appointment->confirmed_by)->toBe($sales->id);
});

it('suggère et fusionne un doublon depuis la fiche contact', function () {
    $this->actingAs(User::factory()->admin()->withMfa()->create());
    $kept = makeLead([], ['full_name' => 'Awa Diarra', 'phone_e164' => '+22374666489'])->contact;
    $duplicate = makeLead([], ['full_name' => 'A. Diarra', 'phone_e164' => '+22374666489'])->contact;

    $this->get('/admin/contacts/'.$kept->id)->assertOk()->assertSee('A. Diarra');

    Livewire::test(ViewContact::class, ['record' => $kept->getRouteKey()])
        ->callAction('merge', ['duplicate_id' => $duplicate->id])
        ->assertNotified('Contacts fusionnés');

    expect(Contact::withTrashed()->find($duplicate->id)->merged_into_id)->toBe($kept->id);
});

it('renvoie un envoi en échec depuis l\'écran « Envois »', function () {
    $this->actingAs(User::factory()->admin()->withMfa()->create());
    $message = OutboundMessage::query()->create([
        'channel' => 'email', 'to' => 'equipe@example.test', 'template' => 'lead_new_internal',
        'payload' => ['lead_id' => 1, 'type' => 'contact'], 'status' => 'failed', 'attempts' => 1, 'last_error' => 'SMTP',
    ]);

    Livewire::test(ListOutboundMessages::class)
        ->assertSee('equipe@example.test')
        ->callTableAction('resend', $message)
        ->assertNotified('Message envoyé');

    expect($message->refresh()->status)->toBe(OutboundStatus::Sent);
});

it('affiche les widgets du tableau de bord au commercial', function () {
    makeLead([], ['full_name' => 'Kadiatou Traoré']);

    $this->actingAs(User::factory()->sales()->create())->get('/admin')
        ->assertOk()
        ->assertSee('Leads à traiter')
        ->assertSee('Rendez-vous à confirmer');
});
