<?php

namespace App\Domain\Diagnostic\Models;

use App\Domain\Diagnostic\Models\Concerns\BelongsToTemplate;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $template_id
 * @property string $key
 * @property string $name
 * @property array<string, mixed> $conditions
 * @property list<array<string, mixed>> $effects
 * @property string $explanation
 * @property int $priority
 * @property bool $is_active
 */
class DiagnosticRule extends Model
{
    use BelongsToTemplate;

    protected $fillable = [
        'template_id', 'key', 'name', 'conditions', 'effects', 'explanation', 'priority', 'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
        'explanation' => '',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'effects' => 'array',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDefinitionArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'priority' => $this->priority,
            'conditions' => $this->conditions,
            'effects' => $this->effects,
            'explanation' => $this->explanation,
            'is_active' => $this->is_active,
        ];
    }
}
