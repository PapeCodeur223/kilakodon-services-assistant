<?php

use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\AuditLog;
use App\Domain\Platform\Models\User;
use App\Domain\Platform\Services\Settings as SettingsService;
use App\Filament\Pages\Settings;
use Database\Seeders\SettingsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

it('affiche les paramètres actuels à un admin', function () {
    $this->actingAs(User::factory()->admin()->withMfa()->create());

    Livewire::test(Settings::class)
        ->assertOk()
        ->assertSchemaStateSet([
            'whatsapp_primary' => '+22374666489',
            'email' => 'kilakodon@gmail.com',
            'ai_enabled' => false,
        ]);
});

it('enregistre les paramètres modifiés et les journalise', function () {
    $admin = User::factory()->admin()->withMfa()->create();
    $this->actingAs($admin);

    Livewire::test(Settings::class)
        ->fillForm(['hours' => 'Lundi–vendredi, 8 h–17 h', 'ai_max_messages_per_conversation' => 15])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(SettingsService::class);
    expect($settings->get('contact.hours'))->toBe('Lundi–vendredi, 8 h–17 h')
        ->and($settings->get('ai.max_messages_per_conversation'))->toBe(15);

    $log = AuditLog::query()->where('action', AuditAction::SettingChanged)->latest('id')->firstOrFail();
    expect($log->user_id)->toBe($admin->id)
        ->and($log->changes['after']['key'])->toBe('ai.max_messages_per_conversation');
});

it('versionne les textes de consentement quand ils changent', function () {
    $this->actingAs(User::factory()->admin()->withMfa()->create());

    Livewire::test(Settings::class)
        ->fillForm(['consent_marketing' => 'Je souhaite recevoir des actualités.'])
        ->call('save');

    expect(app(SettingsService::class)->get('consent.texts'))
        ->toMatchArray(['version' => 2, 'marketing' => 'Je souhaite recevoir des actualités.']);
});

it('valide le format des numéros et des e-mails', function () {
    $this->actingAs(User::factory()->admin()->withMfa()->create());

    Livewire::test(Settings::class)
        ->fillForm(['whatsapp_primary' => '74 66 64 89', 'lead_recipients' => ['pas-un-email']])
        ->call('save')
        ->assertHasFormErrors(['whatsapp_primary', 'lead_recipients.0']);
});

it('refuse la page et l\'enregistrement aux rôles non admin', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create());

    Livewire::test(Settings::class)->assertForbidden();
})->with(['sales', 'editor']);
