<?php

namespace App\Domain\Diagnostic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $session_id
 * @property string $question_key
 * @property string|list<string>|int|float $value chaîne, tableau, nombre ou « __unknown »
 */
class DiagnosticAnswer extends Model
{
    protected $fillable = ['session_id', 'question_key', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    /**
     * @return BelongsTo<DiagnosticSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(DiagnosticSession::class, 'session_id');
    }
}
