<?php

use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\AuditLog;
use App\Domain\Platform\Models\User;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use Livewire\Livewire;

it('permet à un admin de créer un utilisateur', function () {
    $this->actingAs(User::factory()->admin()->withMfa()->create());

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Awa Traoré',
            'email' => 'awa@kilakodon.test',
            'role' => 'sales',
            'is_active' => true,
            'password' => 'motdepasse-solide-1',
            'password_confirmation' => 'motdepasse-solide-1',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::query()->where('email', 'awa@kilakodon.test')->first()?->role)->toBe(UserRole::Sales);
});

it('refuse un mot de passe de moins de 12 caractères', function () {
    $this->actingAs(User::factory()->admin()->withMfa()->create());

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Test',
            'email' => 'test@kilakodon.test',
            'role' => 'editor',
            'password' => 'court1',
            'password_confirmation' => 'court1',
        ])
        ->call('create')
        ->assertHasFormErrors(['password']);
});

it('journalise un changement de rôle', function () {
    $admin = User::factory()->admin()->withMfa()->create();
    $editor = User::factory()->editor()->create();
    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $editor->getRouteKey()])
        ->fillForm(['role' => 'sales'])
        ->call('save')
        ->assertHasNoFormErrors();

    $log = AuditLog::query()->where('action', AuditAction::RoleChanged)->latest('id')->firstOrFail();
    expect($log->user_id)->toBe($admin->id)
        ->and($log->auditable_id)->toBe($editor->id)
        ->and($log->changes)->toBe(['before' => ['role' => 'editor'], 'after' => ['role' => 'sales']]);
});

it('empêche un admin de modifier son propre rôle, même en forçant le formulaire', function () {
    $admin = User::factory()->admin()->withMfa()->create();
    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
        ->set('data.role', 'editor')
        ->set('data.is_active', false)
        ->call('save');

    $admin->refresh();
    expect($admin->role)->toBe(UserRole::Admin)
        ->and($admin->is_active)->toBeTrue();
});

it('ne journalise jamais le mot de passe ni le secret MFA', function () {
    $user = User::factory()->withMfa()->create();
    $user->update(['password' => 'nouveau-mot-de-passe-1']);

    AuditLog::query()->where('auditable_id', $user->id)->get()->each(function (AuditLog $log) {
        $json = json_encode($log->changes);
        expect($json)->not->toContain('password')
            ->and($json)->not->toContain('app_authentication');
    });
});
