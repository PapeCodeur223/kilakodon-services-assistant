<?php

namespace App\Filament\Resources\OutboundMessages;

use App\Domain\Platform\Actions\ResendOutboundMessage;
use App\Domain\Platform\Enums\OutboundStatus;
use App\Domain\Platform\Models\OutboundMessage;
use App\Filament\Resources\OutboundMessages\Pages\ListOutboundMessages;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * « Envois » (COM-01) : file des e-mails, statut visible, renvoi sans rejouer l'action métier.
 */
class OutboundMessageResource extends Resource
{
    protected static ?string $model = OutboundMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static string|UnitEnum|null $navigationGroup = 'Réglages';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Envois';

    protected static ?string $modelLabel = 'envoi';

    protected static ?string $pluralModelLabel = 'envois';

    public static function getNavigationBadge(): ?string
    {
        $count = OutboundMessage::query()->whereIn('status', [OutboundStatus::Failed, OutboundStatus::Abandoned])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        $tz = config('kh.display_timezone');

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Créé le')->dateTime('d/m/Y H:i', timezone: $tz)->sortable(),
                TextColumn::make('template')->label('Modèle')->fontFamily('mono'),
                TextColumn::make('to')->label('Destinataire')->searchable(),
                TextColumn::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (OutboundStatus $state): string => $state->label())
                    ->color(fn (OutboundStatus $state): string => match ($state) {
                        OutboundStatus::Sent => 'success',
                        OutboundStatus::Queued => 'info',
                        OutboundStatus::Failed => 'warning',
                        OutboundStatus::Abandoned => 'danger',
                    }),
                TextColumn::make('attempts')->label('Tentatives'),
                TextColumn::make('last_error')->label('Dernière erreur')->limit(60)->placeholder('—')->wrap(),
                TextColumn::make('sent_at')->label('Envoyé le')->dateTime('d/m/Y H:i', timezone: $tz)->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(OutboundStatus::options()),
            ])
            ->recordActions([
                Action::make('resend')
                    ->label('Renvoyer')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->requiresConfirmation()
                    ->visible(fn (OutboundMessage $record): bool => auth()->user()?->can('resend', $record) === true)
                    ->action(function (OutboundMessage $record): void {
                        app(ResendOutboundMessage::class)->handle($record);
                        $sent = $record->refresh()->status === OutboundStatus::Sent;
                        Notification::make()
                            ->title($sent ? 'Message envoyé' : 'Renvoi programmé')
                            ->status($sent ? 'success' : 'info')
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOutboundMessages::route('/'),
        ];
    }
}
