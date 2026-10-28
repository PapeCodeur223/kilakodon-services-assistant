<?php

namespace App\Filament\Resources\DiagnosticTemplates\RelationManagers;

use App\Domain\Diagnostic\Enums\QuestionType;
use App\Domain\Diagnostic\Models\DiagnosticTemplate;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

/**
 * Questions d'un questionnaire : modifiables uniquement en brouillon.
 */
class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Questions';

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
            Select::make('step')->label('Étape')->options([1 => '1', 2 => '2', 3 => '3', 4 => '4'])->required(),
            Select::make('type')->label('Type')->options(QuestionType::options())->required(),
            TextInput::make('label')->label('Libellé')->required()->maxLength(255)->columnSpan(2),
            TextInput::make('sort')->label('Ordre')->integer()->default(0),
            TextInput::make('help')->label('Aide')->maxLength(255)->columnSpan(2),
            TextInput::make('max_selections')->label('Choix maximum')->integer()->minValue(1),
            Toggle::make('required')->label('Obligatoire'),
            Toggle::make('allow_unknown')->label('« Je ne sais pas »'),
            JsonField::make('options', 'Options', 'Liste de {"value", "label", "show_if"?, "exclusive"?}.'),
            JsonField::make('show_if', 'Condition d\'affichage', 'Format TRD §6 : {"all": [{"q": "sector", "op": "eq", "v": "school"}]}. Vide = toujours visible.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                TextColumn::make('step')->label('Étape'),
                TextColumn::make('key')->label('Clé')->fontFamily('mono'),
                TextColumn::make('label')->label('Libellé')->wrap(),
                TextColumn::make('type')->label('Type')->formatStateUsing(fn (QuestionType $state): string => $state->label()),
                IconColumn::make('required')->label('Obligatoire')->boolean(),
                IconColumn::make('show_if')->label('Conditionnelle')->boolean()->getStateUsing(fn (Model $record): bool => filled($record->getAttribute('show_if'))),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()]);
    }
}
