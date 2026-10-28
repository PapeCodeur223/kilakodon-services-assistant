<?php

namespace App\Domain\Diagnostic\Engine;

/**
 * Règle déterministe : conditions → effets (`recommend`, `pain`, `clarify`) — TRD §6.
 *
 * @phpstan-type Effect array{type: string, module?: string, weight?: int, key?: string}
 */
final readonly class RuleDefinition
{
    public const RECOMMEND = 'recommend';

    public const PAIN = 'pain';

    public const CLARIFY = 'clarify';

    /**
     * @param  array<string, mixed>  $conditions
     * @param  list<Effect>  $effects
     */
    public function __construct(
        public string $key,
        public int $priority,
        public array $conditions,
        public array $effects,
        public string $explanation = '',
        public bool $isActive = true,
        public string $name = '',
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        $effects = [];
        foreach ((array) ($row['effects'] ?? []) as $effect) {
            $effect = (array) $effect;
            $effects[] = array_filter([
                'type' => (string) $effect['type'],
                'module' => isset($effect['module']) ? (string) $effect['module'] : null,
                'weight' => isset($effect['weight']) ? (int) $effect['weight'] : null,
                'key' => isset($effect['key']) ? (string) $effect['key'] : null,
            ], fn (mixed $v): bool => $v !== null);
        }

        return new self(
            key: (string) $row['key'],
            priority: (int) $row['priority'],
            conditions: (array) ($row['conditions'] ?? []),
            effects: $effects,
            explanation: (string) ($row['explanation'] ?? ''),
            isActive: (bool) ($row['is_active'] ?? true),
            name: (string) ($row['name'] ?? $row['key']),
        );
    }
}
