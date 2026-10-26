<?php

use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\AuditLog;
use App\Domain\Platform\Models\User;
use App\Domain\Platform\Support\IpHasher;
use Illuminate\Auth\Events\Login;

it('journalise la connexion et met à jour last_login_at', function () {
    $user = User::factory()->sales()->create();

    event(new Login('web', $user, false));

    $log = AuditLog::query()->where('action', AuditAction::Login)->firstOrFail();
    expect($log->user_id)->toBe($user->id)
        ->and($user->fresh()->last_login_at)->not->toBeNull();
});

it("stocke l'IP hachée, jamais en clair", function () {
    $this->withServerVariables(['REMOTE_ADDR' => '196.200.1.42']);
    $user = User::factory()->create();
    $this->get('/');

    event(new Login('web', $user, false));

    $log = AuditLog::query()->where('action', AuditAction::Login)->latest('id')->firstOrFail();
    expect($log->ip_hash)->toHaveLength(64)
        ->and($log->ip_hash)->not->toContain('196.200')
        ->and($log->ip_hash)->toBe(app(IpHasher::class)->hash((string) request()->ip()));
});

it("journalise la création d'un utilisateur sans données sensibles", function () {
    $user = User::factory()->withMfa()->create(['email' => 'nouveau@kilakodon.test']);

    $log = AuditLog::query()->where('action', AuditAction::Created)->where('auditable_id', $user->id)->firstOrFail();

    expect($log->changes['after']['email'])->toBe('nouveau@kilakodon.test')
        ->and($log->changes['after'])->not->toHaveKeys(['password', 'app_authentication_secret', 'app_authentication_recovery_codes', 'remember_token']);
});

it("distingue un changement de rôle d'une modification simple", function () {
    $user = User::factory()->editor()->create();

    $user->update(['name' => 'Nouveau nom']);
    $user->update(['role' => 'admin']);

    $actions = AuditLog::query()->where('auditable_id', $user->id)->orderBy('id')->pluck('action')->all();
    expect($actions)->toBe([AuditAction::Created, AuditAction::Updated, AuditAction::RoleChanged]);
});
