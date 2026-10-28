<?php

namespace App\Filament\Resources\Leads;

use App\Domain\Diagnostic\Models\DiagnosticQuestion;
use App\Domain\Diagnostic\Models\DiagnosticTemplate;

/**
 * Libellés des réponses déclarées (budget, échéance, rôle) repris du questionnaire actif :
 * une seule source pour le prospect et le commercial (CRM-03).
 */
final class QualificationOptions
{
    /** @var array<string, array<string, string>> */
    private static array $cache = [];

    /**
     * @return array<string, string>
     */
    public static function for(string $questionKey): array
    {
        if (isset(self::$cache[$questionKey])) {
            return self::$cache[$questionKey];
        }

        $templateId = DiagnosticTemplate::query()->active()->value('id');
        $options = DiagnosticQuestion::query()->where('template_id', $templateId)->where('key', $questionKey)->value('options');
        $options = is_string($options) ? (array) json_decode($options, true) : (array) $options;

        return self::$cache[$questionKey] = collect($options)
            ->mapWithKeys(fn (mixed $o): array => is_array($o) ? [(string) $o['value'] => (string) $o['label']] : [])
            ->all();
    }

    public static function label(string $questionKey, ?string $value): ?string
    {
        return $value === null ? null : (self::for($questionKey)[$value] ?? $value);
    }

    /**
     * @return array<int, string>
     */
    public static function fit(): array
    {
        return [1 => '1 — Faible', 2 => '2 — Moyenne', 3 => '3 — Forte'];
    }
}
