<?php

namespace App\Filament\Resources\DiagnosticTemplates\RelationManagers;

use Filament\Forms\Components\Textarea;

/**
 * Champ JSON (conditions, options, effets) : affiché indenté, validé, enregistré en tableau.
 */
final class JsonField
{
    public static function make(string $name, string $label, ?string $help = null, bool $required = false): Textarea
    {
        return Textarea::make($name)
            ->label($label)
            ->helperText($help)
            ->rows(8)
            ->extraInputAttributes(['class' => 'font-mono text-xs', 'spellcheck' => 'false'])
            ->required($required)
            ->rule('json')
            ->formatStateUsing(fn (mixed $state): ?string => is_array($state)
                ? (string) json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : $state)
            ->dehydrateStateUsing(fn (mixed $state): mixed => is_string($state) && trim($state) !== '' ? json_decode($state, true) : null)
            ->columnSpanFull();
    }
}
