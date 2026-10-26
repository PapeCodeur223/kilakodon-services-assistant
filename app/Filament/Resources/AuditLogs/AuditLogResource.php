<?php

namespace App\Filament\Resources\AuditLogs;

use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\AuditLog;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Journal d'audit — lecture seule, administrateurs uniquement (AuditLogPolicy).
 */
class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Réglages';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Journal';

    protected static ?string $modelLabel = 'entrée du journal';

    protected static ?string $pluralModelLabel = 'journal d\'audit';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'auditable']);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextEntry::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i:s', timezone: config('kh.display_timezone')),
                TextEntry::make('user.name')
                    ->label('Auteur')
                    ->placeholder('Système'),
                TextEntry::make('action')
                    ->label('Action')
                    ->badge()
                    ->formatStateUsing(fn (AuditAction $state): string => $state->label()),
                TextEntry::make('auditable_type')
                    ->label('Objet')
                    ->formatStateUsing(fn (AuditLog $record): string => static::subjectLabel($record))
                    ->placeholder('—'),
                TextEntry::make('ip_hash')
                    ->label('IP (hachée)')
                    ->placeholder('—')
                    ->columnSpanFull(),
                CodeEntry::make('changes')
                    ->label('Changements')
                    ->state(fn (AuditLog $record): string => json_encode($record->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i', timezone: config('kh.display_timezone'))
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Auteur')
                    ->placeholder('Système')
                    ->searchable(),
                TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->formatStateUsing(fn (AuditAction $state): string => $state->label())
                    ->color(fn (AuditAction $state): string => match ($state) {
                        AuditAction::RoleChanged, AuditAction::SettingChanged, AuditAction::Exported, AuditAction::Anonymized => 'warning',
                        AuditAction::Deleted => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('auditable_type')
                    ->label('Objet')
                    ->formatStateUsing(fn (AuditLog $record): string => static::subjectLabel($record))
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Action')
                    ->options(collect(AuditAction::cases())->mapWithKeys(fn (AuditAction $a): array => [$a->value => $a->label()])->all()),
                SelectFilter::make('user')
                    ->label('Auteur')
                    ->relationship('user', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'view' => ViewAuditLog::route('/{record}'),
        ];
    }

    protected static function subjectLabel(AuditLog $record): string
    {
        $type = match ($record->auditable_type) {
            'user' => 'Utilisateur',
            'setting' => 'Paramètre',
            default => (string) $record->auditable_type,
        };

        return trim($type.' #'.$record->auditable_id);
    }
}
