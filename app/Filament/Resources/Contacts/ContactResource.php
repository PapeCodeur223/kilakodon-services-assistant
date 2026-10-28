<?php

namespace App\Filament\Resources\Contacts;

use App\Domain\Crm\Actions\MergeContacts;
use App\Domain\Crm\Enums\PreferredChannel;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Support\DuplicateFinder;
use App\Domain\Crm\Support\PhoneNormalizer;
use App\Filament\Resources\Contacts\Pages\EditContact;
use App\Filament\Resources\Contacts\Pages\ListContacts;
use App\Filament\Resources\Contacts\Pages\ViewContact;
use App\Filament\Resources\Leads\LeadActions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use UnitEnum;

/**
 * Contacts (CRM-01) : téléphones en E.164, doublons suggérés, fusion manuelle journalisée.
 */
class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?int $navigationSort = 15;

    protected static ?string $navigationLabel = 'Contacts';

    protected static ?string $modelLabel = 'contact';

    protected static ?string $pluralModelLabel = 'contacts';

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('company')->withCount('leads');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('full_name')->label('Nom')->required()->maxLength(160),
            TextInput::make('job_title')->label('Fonction')->maxLength(120),
            TextInput::make('phone_e164')->label('Téléphone')->tel()->maxLength(30)
                ->rule(fn () => function (string $attribute, mixed $value, \Closure $fail): void {
                    if (filled($value) && PhoneNormalizer::toE164((string) $value) === null) {
                        $fail('Ce numéro ne semble pas valide.');
                    }
                })
                ->dehydrateStateUsing(fn (?string $state): ?string => PhoneNormalizer::toE164($state)),
            TextInput::make('whatsapp_e164')->label('WhatsApp')->tel()->maxLength(30)
                ->dehydrateStateUsing(fn (?string $state): ?string => PhoneNormalizer::toE164($state)),
            TextInput::make('email')->label('E-mail')->email()->maxLength(190)
                ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? mb_strtolower(trim($state)) : null),
            TextInput::make('city')->label('Ville')->maxLength(80),
            Select::make('preferred_channel')->label('Canal préféré')->options(PreferredChannel::options())->required(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Coordonnées')->columnSpan(2)->columns(2)->schema([
                TextEntry::make('full_name')->label('Nom'),
                TextEntry::make('company.name')->label('Entreprise')->placeholder('—'),
                TextEntry::make('phone_e164')->label('Téléphone')->copyable()->placeholder('—'),
                TextEntry::make('whatsapp_e164')->label('WhatsApp')->copyable()->placeholder('—'),
                TextEntry::make('email')->label('E-mail')->copyable()->placeholder('—'),
                TextEntry::make('preferred_channel')->label('Canal préféré')->formatStateUsing(fn (PreferredChannel $state): string => $state->label()),
                TextEntry::make('marketing_opt_in')->label('Actualités')->formatStateUsing(fn (bool $state): string => $state ? 'Accepte' : 'Non'),
            ]),
            Section::make('Doublons possibles')->columnSpan(1)
                ->description('Même téléphone ou même e-mail. La fusion reste une décision humaine.')
                ->schema([
                    TextEntry::make('duplicates')->hiddenLabel()->listWithLineBreaks()
                        ->getStateUsing(fn (Contact $record): array => app(DuplicateFinder::class)->for($record)
                            ->map(fn (Contact $c): string => '#'.$c->id.' — '.$c->full_name.' ('.($c->phone_e164 ?? $c->email).')')->values()->all() ?: ['Aucun']),
                ]),
            Section::make('Leads')->columnSpanFull()->schema([
                TextEntry::make('leads')->hiddenLabel()->listWithLineBreaks()
                    ->getStateUsing(fn (Contact $record): array => $record->leads()->latest()->get()
                        ->map(fn ($lead): string => '#'.$lead->id.' — '.$lead->type->label().' — '.$lead->stage->label())->all() ?: ['Aucun']),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('full_name')->label('Nom')->searchable()
                    ->description(fn (Contact $record): ?string => $record->company?->name),
                TextColumn::make('phone_e164')->label('Téléphone')->searchable()->placeholder('—'),
                TextColumn::make('email')->label('E-mail')->searchable()->placeholder('—'),
                TextColumn::make('leads_count')->label('Leads'),
                TextColumn::make('created_at')->label('Créé le')->date('d/m/Y', timezone: config('kh.display_timezone'))->sortable(),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }

    public static function mergeAction(): Action
    {
        return Action::make('merge')
            ->label('Fusionner un doublon ici')
            ->icon(Heroicon::OutlinedArrowsPointingIn)
            ->color('warning')
            ->visible(fn (Contact $record): bool => auth()->user()?->can('merge', $record) === true && app(DuplicateFinder::class)->for($record)->isNotEmpty())
            ->modalDescription('Les leads, briefs et consentements du doublon passent sur ce contact, qui est conservé. Le doublon est archivé. L\'opération est journalisée.')
            ->schema([
                Select::make('duplicate_id')
                    ->label('Doublon à fusionner dans ce contact')
                    ->options(fn (Contact $record): array => app(DuplicateFinder::class)->for($record)
                        ->mapWithKeys(fn (Contact $c): array => [$c->id => '#'.$c->id.' — '.$c->full_name.' ('.($c->phone_e164 ?? $c->email).')'])->all())
                    ->required(),
            ])
            ->requiresConfirmation()
            ->action(function (Contact $record, array $data, Component $livewire): void {
                $duplicate = Contact::query()->findOrFail((int) $data['duplicate_id']);
                $done = LeadActions::run(fn () => app(MergeContacts::class)->handle($duplicate, $record, LeadActions::user() ?? abort(403)), 'Contacts fusionnés');
                if ($done) {
                    $livewire->redirect(self::getUrl('view', ['record' => $record]));
                }
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContacts::route('/'),
            'view' => ViewContact::route('/{record}'),
            'edit' => EditContact::route('/{record}/edit'),
        ];
    }
}
