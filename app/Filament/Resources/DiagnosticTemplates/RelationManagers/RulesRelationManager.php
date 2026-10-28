<?php

namespace App\Filament\Resources\DiagnosticTemplates\RelationManagers;

use App\Domain\Diagnostic\Models\DiagnosticRule;
use App\Domain\Diagnostic\Models\DiagnosticTemplate;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

/**
 * Règles déterministes : elles décident des recommandations (l'IA ne fait que reformuler).
 * Modifiables uniquement en brouillon ; la cohérence est vérifiée à l'activation.
 */
class RulesRelationManager extends RelationManager
{
    protected static string $relationship = 'rules';

    protected static ?string $title = 'Règles';

    public function isReadOnly(): bool
    {
        $owner = $this->getOwnerRecord();

        return ! $owner instanceof DiagnosticTemplate || $owner->isLocked();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            TextInput::make('key')->label('Clé')->required()->maxLength(48)->alphaDash()
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule->where('template_id', $this->getOwnerRecord()->getKey())),
            TextInput::make('name')->label('Nom')->required()->maxLength(160),
            TextInput::make('priority')->label('Priorité')->integer()->minValue(0)->required()->helperText('Plus petit = évalué en premier.'),
            TextInput::make('explanation')->label('Explication « Pourquoi ? »')->maxLength(255)->columnSpan(2)
                ->helperText('Phrase affichée au prospect.'),
            Toggle::make('is_active')->label('Active')->default(true),
            JsonField::make('conditions', 'Conditions', 'Format TRD §6 : blocs all / any / none.', required: true),
            JsonField::make('effects', 'Effets', 'Liste de {"type": "recommend", "module", "weight"} | {"type": "pain", "key"} | {"type": "clarify", "key"}.', required: true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('key')
            ->defaultSort('priority')
            ->columns([
                TextColumn::make('priority')->label('Priorité')->sortable(),
                TextColumn::make('key')->label('Clé')->fontFamily('mono'),
                TextColumn::make('explanation')->label('Explication')->wrap()->placeholder('—'),
                TextColumn::make('effects')->label('Effets')->getStateUsing(fn (DiagnosticRule $record): string => collect($record->effects)
                    ->map(fn (array $e): string => match ($e['type'] ?? '') {
                        'recommend' => ($e['module'] ?? '?').' +'.($e['weight'] ?? 0),
                        default => ($e['type'] ?? '?').' '.($e['key'] ?? ''),
                    })->implode(' · '))->wrap(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()]);
    }
}
