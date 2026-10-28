<?php

namespace App\Domain\Diagnostic\Models\Concerns;

use App\Domain\Diagnostic\Exceptions\TemplateLockedException;
use App\Domain\Diagnostic\Models\DiagnosticTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Questions et règles : aucune écriture quand le template est verrouillé (actif ou retiré).
 *
 * @property-read DiagnosticTemplate $template
 *
 * @mixin Model
 */
trait BelongsToTemplate
{
    public static function bootBelongsToTemplate(): void
    {
        $guard = function (self $model): void {
            $template = DiagnosticTemplate::query()->find($model->template_id ?? $model->getOriginal('template_id'));

            if ($template?->isLocked()) {
                throw TemplateLockedException::make();
            }
        };

        static::saving($guard);
        static::deleting($guard);
    }

    /**
     * @return BelongsTo<DiagnosticTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(DiagnosticTemplate::class, 'template_id');
    }
}
