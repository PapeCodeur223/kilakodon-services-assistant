<?php

namespace App\Domain\Platform\Actions;

use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\AuditLog;
use App\Domain\Platform\Models\User;
use App\Domain\Platform\Support\IpHasher;
use Illuminate\Database\Eloquent\Model;

class RecordAudit
{
    public function __construct(private readonly IpHasher $ipHasher) {}

    /**
     * @param  array<string, mixed>|null  $changes  avant/après, sans secrets
     */
    public function handle(AuditAction $action, ?Model $subject = null, ?array $changes = null, ?User $actor = null): AuditLog
    {
        $actor ??= auth()->user() instanceof User ? auth()->user() : null;
        $ip = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request()->ip();

        $log = new AuditLog([
            'action' => $action,
            'changes' => $changes,
            'ip_hash' => $ip !== null ? $this->ipHasher->hash($ip) : null,
        ]);
        $log->user()->associate($actor);

        if ($subject !== null) {
            $log->auditable()->associate($subject);
        }

        $log->save();

        return $log;
    }
}
