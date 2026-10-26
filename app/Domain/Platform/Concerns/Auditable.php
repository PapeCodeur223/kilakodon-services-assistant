<?php

namespace App\Domain\Platform\Concerns;

use App\Domain\Platform\Actions\RecordAudit;
use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Journalise création, modification et suppression d'un modèle.
 * Les attributs cachés ($hidden) et ceux de auditExcluded() ne sont jamais écrits.
 *
 * @mixin Model
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (self $model): void {
            $model->writeAudit(AuditAction::Created, ['after' => $model->auditableAttributes(array_keys($model->getAttributes()))]);
        });

        static::updated(function (self $model): void {
            $keys = array_keys($model->getChanges());
            $after = $model->auditableAttributes($keys);

            if ($after === []) {
                return;
            }

            $before = array_intersect_key($model->getPrevious(), $after);
            $model->writeAudit($model->auditActionForUpdate($after), ['before' => $before, 'after' => $after]);
        });

        static::deleted(function (self $model): void {
            $model->writeAudit(AuditAction::Deleted, null);
        });
    }

    /**
     * @return MorphMany<AuditLog, $this>
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /**
     * Permet à un modèle de qualifier une modification (ex. changement de rôle).
     *
     * @param  array<string, mixed>  $after
     */
    protected function auditActionForUpdate(array $after): AuditAction
    {
        return AuditAction::Updated;
    }

    /**
     * @return list<string>
     */
    protected function auditExcluded(): array
    {
        return ['created_at', 'updated_at', 'remember_token'];
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    protected function auditableAttributes(array $keys): array
    {
        $excluded = array_merge($this->getHidden(), $this->auditExcluded());
        $keys = array_values(array_diff($keys, $excluded));

        return array_intersect_key($this->attributesToArray(), array_flip($keys));
    }

    /**
     * @param  array<string, mixed>|null  $changes
     */
    protected function writeAudit(AuditAction $action, ?array $changes): void
    {
        app(RecordAudit::class)->handle($action, $this, $changes);
    }
}
