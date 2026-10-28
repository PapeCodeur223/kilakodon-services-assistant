<?php

namespace App\Domain\Diagnostic\Models;

use App\Domain\Diagnostic\Engine\QuestionDefinition;
use App\Domain\Diagnostic\Engine\RuleDefinition;
use App\Domain\Diagnostic\Engine\TemplateDefinition;
use App\Domain\Diagnostic\Enums\TemplateStatus;
use App\Domain\Diagnostic\Exceptions\TemplateLockedException;
use App\Domain\Platform\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Questionnaire versionné (BACKEND_SCHEMA §4). Un seul `active` à la fois (ActivateDiagnosticTemplate).
 * Un template actif ou retiré est immuable, ainsi que ses questions et ses règles.
 *
 * @property int $id
 * @property string $name
 * @property int $version
 * @property TemplateStatus $status
 * @property Carbon|null $activated_at
 * @property string|null $notes
 * @property-read Collection<int, DiagnosticQuestion> $questions
 * @property-read Collection<int, DiagnosticRule> $rules
 */
class DiagnosticTemplate extends Model
{
    use Auditable;

    /** Seuls champs modifiables une fois le template verrouillé (cycle de vie). */
    private const LIFECYCLE_FIELDS = ['status', 'activated_at', 'updated_at'];

    protected $fillable = ['name', 'version', 'status', 'activated_at', 'notes'];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'status' => TemplateStatus::class,
            'activated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $template): void {
            $original = TemplateStatus::tryFrom((string) $template->getRawOriginal('status'));
            $changed = array_diff(array_keys($template->getDirty()), self::LIFECYCLE_FIELDS);

            if ($original !== TemplateStatus::Draft && $changed !== []) {
                throw TemplateLockedException::make();
            }

            // Cycle de vie à sens unique : brouillon → actif → retiré (jamais de retour en brouillon).
            $next = $template->status;
            $allowed = match ($original) {
                TemplateStatus::Draft => [TemplateStatus::Draft, TemplateStatus::Active],
                TemplateStatus::Active => [TemplateStatus::Active, TemplateStatus::Retired],
                default => [TemplateStatus::Retired],
            };
            if ($template->isDirty('status') && ! in_array($next, $allowed, true)) {
                throw TemplateLockedException::make();
            }
        });

        static::deleting(function (self $template): void {
            if ($template->isLocked()) {
                throw TemplateLockedException::make();
            }
        });
    }

    public function isLocked(): bool
    {
        return $this->status !== TemplateStatus::Draft;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', TemplateStatus::Active);
    }

    /**
     * @return HasMany<DiagnosticQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(DiagnosticQuestion::class, 'template_id')->orderBy('step')->orderBy('sort')->orderBy('id');
    }

    /**
     * @return HasMany<DiagnosticRule, $this>
     */
    public function rules(): HasMany
    {
        return $this->hasMany(DiagnosticRule::class, 'template_id')->orderBy('priority')->orderBy('id');
    }

    /**
     * Forme consommée par le moteur.
     */
    public function toDefinition(): TemplateDefinition
    {
        $this->loadMissing(['questions', 'rules']);

        return new TemplateDefinition(
            $this->version,
            $this->questions->map(fn (DiagnosticQuestion $q): QuestionDefinition => QuestionDefinition::fromArray($q->toDefinitionArray()))->values()->all(),
            $this->rules->map(fn (DiagnosticRule $r): RuleDefinition => RuleDefinition::fromArray($r->toDefinitionArray()))->values()->all(),
        );
    }

    /**
     * @return list<string>
     */
    protected function auditExcluded(): array
    {
        return ['created_at', 'updated_at'];
    }
}
