<?php

use App\Domain\Brief\Actions\RequestBrief;
use App\Domain\Crm\Data\ProspectDetails;
use App\Domain\Crm\Enums\AppointmentStatus;
use App\Domain\Crm\Models\Appointment;
use App\Domain\Crm\Models\Lead;
use Spatie\Honeypot\Honeypot;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function appointmentForm(array $overrides = []): array
{
    // Champs du honeypot calculés à l'affichage, puis envoi quelques secondes plus tard (comme un humain).
    $honeypot = app(Honeypot::class);
    $fields = [$honeypot->nameFieldName() => '', $honeypot->validFromFieldName() => $honeypot->encryptedValidFrom()];
    test()->travel(5)->seconds();

    return array_merge($fields, [
        'topic' => 'Voir une démonstration de la caisse',
        'channel' => 'whatsapp',
        'slots' => [
            ['date' => now('Africa/Bamako')->addDays(3)->toDateString(), 'period' => 'morning'],
            ['date' => '', 'period' => ''],
        ],
        'name' => 'Oumar Konaté',
        'phone' => '66 12 34 56',
        'email' => 'oumar@example.test',
        'consent_processing' => '1',
    ], $overrides);
}

it('affiche le formulaire de rendez-vous, indexable, sans JavaScript requis', function () {
    $this->get('/rendez-vous')
        ->assertOk()
        ->assertSee('Créneaux souhaités')
        ->assertSee('Appel WhatsApp')
        ->assertSee("Votre rendez-vous n'est confirmé qu'après", false)
        ->assertDontSee('noindex');
});

it('enregistre une demande « demandée » et ne dit jamais « confirmé » (RDV-01)', function () {
    $this->post('/rendez-vous', appointmentForm())
        ->assertRedirect('/rendez-vous')
        ->assertSessionHas('appointment.success', 'Demande reçue — un conseiller vous confirme le créneau.');

    $appointment = Appointment::query()->sole();
    expect($appointment->status)->toBe(AppointmentStatus::Requested)
        ->and($appointment->preferred_slots)->toHaveCount(1)
        ->and(Lead::query()->count())->toBe(1);
});

it('valide les créneaux : au moins un, à partir de demain, date et moment ensemble', function () {
    $this->post('/rendez-vous', appointmentForm(['slots' => [['date' => '', 'period' => '']]]))->assertSessionHasErrors('slots.0.date');
    $this->post('/rendez-vous', appointmentForm(['slots' => [['date' => now()->toDateString(), 'period' => 'morning']]]))->assertSessionHasErrors('slots.0.date');
    $this->post('/rendez-vous', appointmentForm(['slots' => [['date' => now()->addDays(2)->toDateString(), 'period' => '']]]))->assertSessionHasErrors('slots.0.period');
    $this->post('/rendez-vous', appointmentForm(['consent_processing' => null]))->assertSessionHasErrors('consent_processing');

    expect(Appointment::query()->count())->toBe(0);
});

it('pré-remplit depuis un brief valide et rattache la demande à son lead', function () {
    ['session' => $session] = completedDiagnostic();
    ['brief' => $brief, 'token' => $token, 'lead' => $lead] = app(RequestBrief::class)
        ->handle($session, new ProspectDetails('Awa Diarra', '74 66 64 89', 'awa@example.test', 'Boutique Awa'));

    $this->get('/rendez-vous?brief='.$token)
        ->assertOk()
        ->assertSee($brief->reference)
        ->assertSee('Boutique Awa')
        ->assertSee('noindex');

    $this->post('/rendez-vous', appointmentForm(['brief' => $token]))->assertRedirect('/rendez-vous');

    expect(Appointment::query()->sole()->lead_id)->toBe($lead->id)
        ->and(Lead::query()->count())->toBe(1);
});

it('ignore un token de brief inconnu', function () {
    $this->get('/rendez-vous?brief=inconnu')->assertOk()->assertDontSee('Demande liée à votre brief');

    $this->post('/rendez-vous', appointmentForm(['brief' => str_repeat('a', 43)]))->assertRedirect('/rendez-vous');
    expect(Lead::query()->count())->toBe(1);
});
