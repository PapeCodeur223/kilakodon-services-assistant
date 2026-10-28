<?php

namespace App\Domain\Diagnostic\Models;

use App\Domain\Diagnostic\Enums\QuestionType;
use App\Domain\Diagnostic\Models\Concerns\BelongsToTemplate;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $template_id
 * @property string $key
 * @property int $step
 * @property string $label
 * @property string|null $help
 * @property QuestionType $type
 * @property list<array<string, mixed>>|null $options
 * @property bool $allow_unknown
 * @property bool $required
 * @property int|null $max_selections
 * @property array<string, mixed>|null $show_if
 * @property int $sort
 */
class DiagnosticQuestion extends Model
{
    use BelongsToTemplate;

    protected $fillable = [
        'template_id', 'key', 'step', 'label', 'help', 'type', 'options', 'allow_unknown',
        'required', 'max_selections', 'show_if', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'step' => 'integer',
            'type' => QuestionType::class,
            'options' => 'array',
            'allow_unknown' => 'boolean',
            'required' => 'boolean',
            'max_selections' => 'integer',
            'show_if' => 'array',
            'sort' => 'integer',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDefinitionArray(): array
    {
        return [
            'key' => $this->key,
            'step' => $this->step,
            'type' => $this->type->value,
            'label' => $this->label,
            'help' => $this->help,
            'required' => $this->required,
            'allow_unknown' => $this->allow_unknown,
            'max_selections' => $this->max_selections,
            'show_if' => $this->show_if,
            'options' => $this->options ?? [],
            'sort' => $this->sort,
        ];
    }
}
