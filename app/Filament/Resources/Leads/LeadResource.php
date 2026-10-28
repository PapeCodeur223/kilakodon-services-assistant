<?php

namespace App\Filament\Resources\Leads;

use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Crm\Enums\LeadType;
use App\Domain\Crm\Models\Lead;
use App\Filament\Resources\Briefs\BriefResource;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\ViewLead;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Liste simple des leads (lot 1). Kanban, étapes et activités : lot 3.
 */
class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static string|UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Leads';

    protected static ?string $modelLabel = 'lead';

    protected static ?string $pluralModelLabel = 'leads';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['contact', 'owner', 'brief']);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Section::make('Demande')->columnSpanFull()->columns(3)->schema([
                TextEntry::make('type')->label('Type')->badge()->formatStateUsing(fn (LeadType $state): string => $state->label()),
                TextEntry::make('stage')->label('Étape')->badge()->formatStateUsing(fn (LeadStage $state): string => $state->label()),
                TextEntry::make('source')->label('Source'),
                TextEntry::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i', timezone: config('kh.display_timezone')),
                TextEntry::make('owner.name')->label('Responsable')->placeholder('Non attribué'),
                TextEntry::make('brief.reference')->label('Brief')->placeholder('—')
                    ->url(fn (Lead $record): ?string => $record->brief !== null ? BriefResource::getUrl('view', ['record' => $record->brief]) : null),
                TextEntry::make('message')->label('Message')->columnSpanFull()->prose(),
            ]),
            Section::make('Contact')->columnSpanFull()->columns(3)->schema([
                TextEntry::make('contact.full_name')->label('Nom'),
                TextEntry::make('contact.phone_e164')->label('Téléphone')->copyable(),
                TextEntry::make('contact.email')->label('E-mail')->placeholder('—')->copyable(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i', timezone: config('kh.display_timezone'))->sortable(),
                TextColumn::make('contact.full_name')->label('Contact')->searchable(),
                TextColumn::make('type')->label('Type')->badge()->formatStateUsing(fn (LeadType $state): string => $state->label()),
                TextColumn::make('stage')->label('Étape')->badge()->formatStateUsing(fn (LeadStage $state): string => $state->label())
                    ->color(fn (LeadStage $state): string => match ($state) {
                        LeadStage::New => 'info',
                        LeadStage::Won => 'success',
                        LeadStage::Lost => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('source')->label('Source'),
                TextColumn::make('owner.name')->label('Responsable')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('stage')->label('Étape')->options(LeadStage::options()),
                SelectFilter::make('type')->label('Type')->options(LeadType::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->color('success')
                    ->visible(fn (Lead $record): bool => filled($record->contact->whatsapp_e164))
                    ->url(fn (Lead $record): string => 'https://wa.me/'.ltrim((string) $record->contact->whatsapp_e164, '+'), shouldOpenInNewTab: true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeads::route('/'),
            'view' => ViewLead::route('/{record}'),
        ];
    }
}
