<?php

use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\AuditLog;
use App\Domain\Platform\Models\User;

it('réserve la gestion des utilisateurs aux admins', function (string $role, bool $allowed) {
    $user = User::factory()->{$role}()->create();
    $other = User::factory()->create();

    expect($user->can('viewAny', User::class))->toBe($allowed)
        ->and($user->can('create', User::class))->toBe($allowed)
        ->and($user->can('update', $other))->toBe($allowed)
        ->and($user->can('changeRole', $other))->toBe($allowed);
})->with([
    'admin' => ['admin', true],
    'sales' => ['sales', false],
    'editor' => ['editor', false],
]);

it('interdit à un admin de modifier son propre rôle', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('changeRole', $admin))->toBeFalse()
        ->and($admin->can('delete', $admin))->toBeFalse();
});

it('réserve les paramètres aux admins', function (string $role, bool $allowed) {
    expect(User::factory()->{$role}()->create()->can('manage-settings'))->toBe($allowed);
})->with([
    'admin' => ['admin', true],
    'sales' => ['sales', false],
    'editor' => ['editor', false],
]);

it('ne permet à personne de modifier le journal d\'audit', function (string $role) {
    $user = User::factory()->{$role}()->create();
    $log = AuditLog::query()->latest('id')->firstOrFail();

    expect($user->can('viewAny', AuditLog::class))->toBe($role === 'admin')
        ->and($user->can('create', AuditLog::class))->toBeFalse()
        ->and($user->can('update', $log))->toBeFalse()
        ->and($user->can('delete', $log))->toBeFalse();
})->with(['admin', 'sales', 'editor']);

it('bloque toute modification d\'une entrée du journal au niveau du modèle', function () {
    User::factory()->create();
    $log = AuditLog::query()->where('action', AuditAction::Created)->firstOrFail();

    expect(fn () => $log->update(['changes' => []]))->toThrow(LogicException::class)
        ->and(fn () => $log->delete())->toThrow(LogicException::class);
});
