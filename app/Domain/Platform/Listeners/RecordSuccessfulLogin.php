<?php

namespace App\Domain\Platform\Listeners;

use App\Domain\Platform\Actions\RecordAudit;
use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\User;
use Illuminate\Auth\Events\Login;

class RecordSuccessfulLogin
{
    public function __construct(private readonly RecordAudit $recordAudit) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $user = $event->user;
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        $this->recordAudit->handle(AuditAction::Login, $user, ['guard' => $event->guard, 'remember' => $event->remember], $user);
    }
}
