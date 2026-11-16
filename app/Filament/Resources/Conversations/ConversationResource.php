<?php

namespace App\Filament\Resources\Conversations;

use App\Domain\Assistant\Enums\ConversationStatus;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Platform\Models\User;
use App\Filament\Resources\Conversations\Pages\ListConversations;
use App\Filament\Resources\Conversations\Pages\ViewConversation;
use App\Filament\Resources\Leads\LeadResource;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * « Conversations » (lecture seule) : l'admin voit tout, le commercial seulement celles de ses leads.
 */
class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Assistant';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Conversations';

    protected static ?string $modelLabel = 'conversation';

    protected static ?string $pluralModelLabel = 'conversations';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('lead.contact');
        /** @var User|null $user */
        $user = auth()->user();

        // Même règle que la politique, appliquée à la liste : le commercial ne voit que ses leads.
        if ($user !== null && ! $user->isAdmin()) {
            $query->whereHas('lead', fn (Builder $q) => $q->where('owner_id', $user->id));
        }

        return $query;
    }

    public static function infolist(Schema $schema): Schema
    {
        $tz = config('kh.display_timezone');

        return $schema->columns(1)->components([
            Section::make('Conversation')->columns(4)->schema([
                TextEntry::make('created_at')->label('Ouverte le')->dateTime('d/m/Y H:i', timezone: $tz),
                TextEntry::make('entry_path')->label('Page d\'entrée')->placeholder('—'),
                TextEntry::make('message_count')->label('Messages'),
                TextEntry::make('lead_id')->label('Lead')->placeholder('Aucun')
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? 'Lead #'.$state : 'Aucun')
                    ->url(fn (Conversation $record): ?string => $record->lead_id !== null ? LeadResource::getUrl('edit', ['record' => $record->lead_id]) : null),
                TextEntry::make('tokens')->label('Jetons (entrée / sortie)')->getStateUsing(fn (Conversation $record): string => number_format($record->tokens_in, 0, ',', ' ').' / '.number_format($record->tokens_out, 0, ',', ' ')),
                TextEntry::make('cost')->label('Coût estimé')->getStateUsing(fn (Conversation $record): string => $record->cost_estimate_usd_micros > 0 ? number_format($record->cost_estimate_usd_micros / 1_000_000, 4, ',', ' ').' $' : 'Non configuré ou nul'),
            ]),
            Section::make('Échanges')->schema([
                ViewEntry::make('messages')->hiddenLabel()->view('filament.conversations.messages'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $tz = config('kh.display_timezone');

        return $table
            ->defaultSort('last_message_at', 'desc')
            ->columns([
                TextColumn::make('last_message_at')->label('Dernier message')->dateTime('d/m/Y H:i', timezone: $tz)->sortable(),
                TextColumn::make('entry_path')->label('Page d\'entrée')->placeholder('—')->limit(40),
                TextColumn::make('message_count')->label('Messages')->sortable(),
                TextColumn::make('status')->label('Statut')->badge()->formatStateUsing(fn (ConversationStatus $state): string => $state->label()),
                TextColumn::make('lead.contact.full_name')->label('Lead')->placeholder('—'),
            ])
            ->filters([
                TernaryFilter::make('lead_id')->label('Liée à un lead')->nullable(),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConversations::route('/'),
            'view' => ViewConversation::route('/{record}'),
        ];
    }
}
