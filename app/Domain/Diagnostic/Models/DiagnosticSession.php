<?php

namespace App\Domain\Diagnostic\Models;

use App\Domain\Brief\Models\Brief;
use App\Domain\Diagnostic\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Session anonyme de diagnostic (DIA-03) : reprise par token haché, expiration à 30 jours,
 * toujours rattachée à la version du questionnaire avec laquelle elle a commencé.
 *
 * @property string $id
 * @property int $template_id
 * @property string $resume_token_hash
 * @property SessionStatus $status
 * @property int $current_step
 * @property string|null $entry
 * @property array<string, mixed>|null $prefill
 * @property string|null $visitor_key
 * @property string $ip_hash
 * @property array<string, string>|null $utm
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property-read DiagnosticTemplate $template
 * @property-read Collection<int, DiagnosticAnswer> $answers
 * @property-read DiagnosticResult|null $result
 * @property-read Brief|null $brief
 */
class DiagnosticSession extends Model
{
    use HasUlids;

    public const LIFETIME_DAYS = 30;

    protected $fillable = [
        'template_id', 'resume_token_hash', 'status', 'current_step', 'entry', 'prefill',
        'visitor_key', 'ip_hash', 'utm', 'started_at', 'completed_at', 'expires_at',
    ];

    protected $hidden = ['resume_token_hash', 'ip_hash'];

    protected $attributes = [
        'status' => 'in_progress',
        'current_step' => 1,
    ];

    protected function casts(): array
    {
        return [
            'status' => SessionStatus::class,
            'current_step' => 'integer',
            'prefill' => 'array',
            'utm' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->status === SessionStatus::Expired || ($this->expires_at !== null && $this->expires_at->isPast());
    }

    /**
     * @return array<string, mixed>
     */
    public function answerMap(): array
    {
        $this->loadMissing('answers');

        return $this->answers->mapWithKeys(fn (DiagnosticAnswer $a): array => [$a->question_key => $a->value])->all();
    }

    /**
     * @return BelongsTo<DiagnosticTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(DiagnosticTemplate::class, 'template_id');
    }

    /**
     * @return HasMany<DiagnosticAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(DiagnosticAnswer::class, 'session_id');
    }

    /**
     * @return HasOne<DiagnosticResult, $this>
     */
    public function result(): HasOne
    {
        return $this->hasOne(DiagnosticResult::class, 'session_id');
    }

    /**
     * @return HasOne<Brief, $this>
     */
    public function brief(): HasOne
    {
        return $this->hasOne(Brief::class, 'diagnostic_session_id');
    }
}
