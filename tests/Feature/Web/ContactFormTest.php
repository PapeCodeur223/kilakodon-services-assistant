<?php

use App\Domain\Crm\Models\Lead;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Honeypot\Honeypot;

/**
 * Champs du honeypot d'un vrai formulaire, rempli après le délai minimal.
 *
 * @return array<string, string>
 */
function honeypotFields(): array
{
    $honeypot = app(Honeypot::class);
    $fields = [$honeypot->nameFieldName() => '', $honeypot->validFromFieldName() => $honeypot->encryptedValidFrom()];
    test()->travel(5)->seconds();

    return $fields;
}

function validContact(array $overrides = []): array
{
    return array_merge([
        'name' => 'Moussa Keïta',
        'phone' => '74 66 64 89',
        'email' => 'moussa@example.test',
        'subject' => 'project',
        'message' => 'Je cherche un logiciel de gestion de stock.',
        'consent_processing' => '1',
    ], honeypotFields(), $overrides);
}

beforeEach(fn () => RateLimiter::clear('contact'));

it('affiche le formulaire avec les consentements séparés et non cochés', function () {
    $response = $this->get('/contact')->assertOk();

    $response->assertSee('name="consent_processing"', false)
        ->assertSee('name="consent_marketing"', false)
        ->assertSee('J&#039;accepte que Kilakodon Services utilise ces informations', false)
        ->assertDontSee('checked', false);
});

it('crée un lead et affiche le message de succès', function () {
    $this->post('/contact', validContact())
        ->assertRedirect('/contact')
        ->assertSessionHas('contact.success');

    expect(Lead::query()->count())->toBe(1);
    $this->followingRedirects()->get('/contact')->assertSee('votre message est bien arrivé');
});

it('ne cite pas d’horaires encore « [À VALIDER] » dans le message de succès', function () {
    $this->post('/contact', validContact())->assertSessionHas('contact.success', fn (string $m) => ! str_contains($m, 'VALIDER'));
});

it('exige le consentement au traitement et un téléphone valide', function () {
    $this->post('/contact', validContact(['consent_processing' => null, 'phone' => '12']))
        ->assertSessionHasErrors(['consent_processing', 'phone']);

    expect(Lead::query()->count())->toBe(0);
});

it('rejette silencieusement un robot qui remplit le honeypot', function () {
    $fields = validContact();
    // Le nom du champ piège est aléatoire : on remplit celui du formulaire.
    $trap = collect($fields)->keys()->first(fn (string $key) => str_starts_with($key, config('honeypot.name_field_name')));
    $fields[$trap] = 'je suis un robot';

    $this->post('/contact', $fields);

    expect(Lead::query()->count())->toBe(0);
});

it('rejette un envoi sans champs honeypot', function () {
    $this->post('/contact', array_diff_key(validContact(), honeypotFields()));

    expect(Lead::query()->count())->toBe(0);
});

it('limite les envois à 5 par heure et par IP', function () {
    for ($i = 1; $i <= 5; $i++) {
        $this->post('/contact', validContact(['message' => "Message numéro {$i} assez long."]))->assertRedirect();
    }

    $this->post('/contact', validContact(['message' => 'Sixième message assez long.']))->assertTooManyRequests();
});

it('ne crée qu’un lead en cas de double soumission', function () {
    $data = validContact();
    $this->post('/contact', $data);
    $this->post('/contact', $data);

    expect(Lead::query()->count())->toBe(1);
});
