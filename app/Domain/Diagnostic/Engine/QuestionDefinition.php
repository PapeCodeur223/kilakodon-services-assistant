<?php

namespace App\Domain\Diagnostic\Engine;

/**
 * Question d'un template, indépendante de la base (le moteur reste testable en PHP pur).
 *
 * @phpstan-type Option array{value: string, label: string, hint?: string|null, show_if?: array<string, mixed>|null, exclusive?: bool}
 */
final readonly class QuestionDefinition
{
    public const SINGLE = 'single';

    public const MULTI = 'multi';

    public const TEXT = 'text';

    public const NUMBER = 'number';

    /**
     * @param  array<string, mixed>|null  $showIf
     * @param  list<Option>  $options
     */
    public function __construct(
        public string $key,
        public int $step,
        public string $type,
        public string $label,
        public ?string $help = null,
        public bool $required = false,
        public bool $allowUnknown = false,
        public ?int $maxSelections = null,
        public ?array $showIf = null,
        public array $options = [],
        public int $sort = 0,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row, int $sort = 0): self
    {
        $options = [];
        foreach ((array) ($row['options'] ?? []) as $option) {
            $option = (array) $option;
            $options[] = array_filter([
                'value' => (string) $option['value'],
                'label' => (string) $option['label'],
                'hint' => isset($option['hint']) ? (string) $option['hint'] : null,
                'show_if' => isset($option['show_if']) ? (array) $option['show_if'] : null,
                'exclusive' => (bool) ($option['exclusive'] ?? false),
            ], fn (mixed $v): bool => $v !== null);
        }

        return new self(
            key: (string) $row['key'],
            step: (int) $row['step'],
            type: (string) $row['type'],
            label: (string) $row['label'],
            help: isset($row['help']) ? (string) $row['help'] : null,
            required: (bool) ($row['required'] ?? false),
            allowUnknown: (bool) ($row['allow_unknown'] ?? false),
            maxSelections: isset($row['max_selections']) ? (int) $row['max_selections'] : null,
            showIf: isset($row['show_if']) && $row['show_if'] !== [] ? (array) $row['show_if'] : null,
            options: $options,
            sort: (int) ($row['sort'] ?? $sort),
        );
    }

    public function isMulti(): bool
    {
        return $this->type === self::MULTI;
    }

    public function hasOptions(): bool
    {
        return in_array($this->type, [self::SINGLE, self::MULTI], true);
    }

    /**
     * @return Option|null
     */
    public function option(string $value): ?array
    {
        foreach ($this->options as $option) {
            if ($option['value'] === $value) {
                return $option;
            }
        }

        return null;
    }

    public function optionLabel(string $value): string
    {
        if ($value === Catalog::UNKNOWN) {
            return 'Je ne sais pas';
        }

        return $this->option($value)['label'] ?? $value;
    }
}
