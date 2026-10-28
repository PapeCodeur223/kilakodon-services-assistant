<?php

namespace App\Domain\Diagnostic\Models;

use App\Domain\Diagnostic\Engine\DiagnosticOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Restitution calculée par le moteur ; `ai_summary` n'est qu'une reformulation facultative.
 *
 * @property int $id
 * @property string $session_id
 * @property int $template_version
 * @property string $maturity_score
 * @property int $maturity_level
 * @property list<array<string, mixed>> $pains
 * @property list<array<string, mixed>> $recommendations
 * @property list<array<string, mixed>> $clarifications
 * @property string|null $ai_summary
 * @property string|null $ai_summary_model
 * @property Carbon $computed_at
 * @property-read DiagnosticSession $session
 */
class DiagnosticResult extends Model
{
    protected $fillable = [
        'session_id', 'template_version', 'maturity_score', 'maturity_level', 'pains',
        'recommendations', 'clarifications', 'ai_summary', 'ai_summary_model', 'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'template_version' => 'integer',
            'maturity_score' => 'decimal:1',
            'maturity_level' => 'integer',
            'pains' => 'array',
            'recommendations' => 'array',
            'clarifications' => 'array',
            'computed_at' => 'datetime',
        ];
    }

    public function toOutcome(): DiagnosticOutcome
    {
        return DiagnosticOutcome::fromArray([
            'template_version' => $this->template_version,
            'maturity_score' => (float) $this->maturity_score,
            'maturity_level' => $this->maturity_level,
            'pains' => $this->pains,
            'recommendations' => $this->recommendations,
            'clarifications' => $this->clarifications,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function attributesFrom(DiagnosticOutcome $outcome): array
    {
        $data = $outcome->toArray();

        return [...$data, 'maturity_score' => number_format($outcome->maturityScore, 1, '.', ''), 'computed_at' => now()];
    }

    /**
     * @return BelongsTo<DiagnosticSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(DiagnosticSession::class, 'session_id');
    }
}
