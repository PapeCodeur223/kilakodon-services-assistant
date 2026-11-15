<?php

use App\Domain\Brief\Actions\RequestBrief;
use App\Domain\Crm\Actions\ConfirmAppointment;
use App\Domain\Crm\Actions\RequestAppointment;
use App\Domain\Crm\Data\AppointmentRequest;
use App\Domain\Crm\Data\ProspectDetails;
use App\Domain\Crm\Enums\AppointmentChannel;
use App\Domain\Crm\Enums\AppointmentStatus;
use App\Domain\Crm\Enums\ConsentSource;
use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Crm\Enums\LeadType;
use App\Domain\Crm\Exceptions\CrmException;
use App\Domain\Crm\Models\Appointment;
use App\Domain\Crm\Models\Consent;
use App\Domain\Crm\Models\Lead;
use App\Domain\Platform\Models\OutboundMessage;
use App\Domain\Platform\Models\User;
use App\Domain\Platform\Services\Settings;
use Illuminate\Support\Carbon;

function appointmentRequest(array $overrides = []): AppointmentRequest
{
    return new AppointmentRequest(...array_merge([
        'fullName' => 'Ibrahim Sangaré',
        'phone' => '76 12 34 56',
        'email' => 'ibrahim@example.test',
        'company' => 'Pharmacie du Fleuve',
        'topic' => 'Logiciel de gestion de stock',
        'channel' => AppointmentChannel::Whatsapp,
        'slots' => [['date' => now()->addDays(2)->toDateString(), 'period' => 'morning'], ['date' => now()->addDays(3)->toDateString(), 'period' => 'afternoon']],
        'ip' => '196.200.10.1',
    ], $overrides));
}

it('crée une demande au statut « demandé », un lead et les notifications (RDV-01)', function () {
    app(Settings::class)->set('notify.lead_recipients', ['equipe@example.test']);
    $appointment = app(RequestAppointment::class)->handle(appointmentRequest());
    $lead = $appointment->lead()->with('contact')->firstOrFail();

    expect($appointment->status)->toBe(AppointmentStatus::Requested)
        ->and($appointment->scheduled_at)->toBeNull()
        ->and($appointment->timezone)->toBe('Africa/Bamako')
        ->and($appointment->preferred_slots)->toHaveCount(2)
        ->and($lead->type)->toBe(LeadType::Appointment)
        ->and($lead->contact->phone_e164)->toBe('+22376123456')
        ->and(Consent::query()->where('source', ConsentSource::AppointmentForm)->count())->toBe(2)
        ->and(OutboundMessage::query()->pluck('template', 'to')->all())->toBe([
            'equipe@example.test' => 'lead_new_internal',
            'ibrahim@example.test' => 'appointment_ack',
        ]);
});

it('est idempotent pour une double soumission le même jour', function () {
    $first = app(RequestAppointment::class)->handle(appointmentRequest());
    $second = app(RequestAppointment::class)->handle(appointmentRequest());

    expect($second->id)->toBe($first->id)
        ->and(Lead::query()->count())->toBe(1)
        ->and(Appointment::query()->count())->toBe(1);
});

it('rattache la demande au lead du brief quand le visiteur vient de son brief', function () {
    ['session' => $session] = completedDiagnostic();
    ['brief' => $brief, 'lead' => $lead] = app(RequestBrief::class)->handle($session, new ProspectDetails('Awa Diarra', '74 66 64 89', 'awa@example.test'));

    $appointment = app(RequestAppointment::class)->handle(appointmentRequest(['brief' => $brief->refresh(), 'fullName' => 'Autre nom']));

    expect($appointment->lead_id)->toBe($lead->id)
        ->and(Lead::query()->count())->toBe(1)
        ->and($lead->refresh()->contact()->value('full_name'))->toBe('Awa Diarra')
        ->and(OutboundMessage::query()->where('template', 'appointment_requested_internal')->exists())->toBeTrue();
});

it('refuse une demande sans créneau', function () {
    app(RequestAppointment::class)->handle(appointmentRequest(['slots' => []]));
})->throws(CrmException::class);

it('confirme seulement par un humain, avec une date future, et passe le lead en « rendez-vous »', function () {
    $sales = User::factory()->sales()->create();
    $appointment = app(RequestAppointment::class)->handle(appointmentRequest());

    expect(fn () => app(ConfirmAppointment::class)->handle($appointment, now()->subHour(), $sales))->toThrow(CrmException::class);

    $at = Carbon::parse('next monday 10:30', 'Africa/Bamako');
    app(ConfirmAppointment::class)->handle($appointment, $at, $sales, 'Prévoir la démo caisse');

    $appointment->refresh();
    expect($appointment->status)->toBe(AppointmentStatus::Confirmed)
        ->and($appointment->confirmed_by)->toBe($sales->id)
        ->and($appointment->scheduled_at->equalTo($at))->toBeTrue()
        ->and($appointment->lead()->firstOrFail()->stage)->toBe(LeadStage::Meeting);

    $mail = OutboundMessage::query()->where('template', 'appointment_confirmed')->sole();
    expect($mail->payload['time'])->toBe('10:30')
        ->and($mail->payload['channel'])->toBe('Appel WhatsApp');
});

it('annule, réalise ou marque absent selon les transitions autorisées', function () {
    $sales = User::factory()->sales()->create();
    $confirm = app(ConfirmAppointment::class);
    $appointment = app(RequestAppointment::class)->handle(appointmentRequest());

    expect(fn () => $confirm->close($appointment, AppointmentStatus::Done, $sales))->toThrow(CrmException::class);

    $confirm->handle($appointment, now()->addDay(), $sales);
    $confirm->close($appointment->refresh(), AppointmentStatus::Done, $sales, 'Démo faite');

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Done)
        ->and(fn () => $confirm->close($appointment, AppointmentStatus::Cancelled, $sales))->toThrow(CrmException::class);
});
