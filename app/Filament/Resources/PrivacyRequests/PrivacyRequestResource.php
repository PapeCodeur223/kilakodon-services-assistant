<?php

namespace App\Filament\Resources\PrivacyRequests;

use App\Domain\Crm\Enums\PrivacyRequestStatus;
use App\Domain\Crm\Enums\PrivacyRequestType;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\PrivacyRequest;
use App\Filament\Resources\PrivacyRequests\Pages\CreatePrivacyRequest;
use App\Filament\Resources\PrivacyRequests\Pages\EditPrivacyRequest;
use App\Filament\Resources\PrivacyRequests\Pages\ListPrivacyRequests;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
 * Demandes de droits (PRV-02) : vérification d'identité, export JSON, anonymisation — admin seulement.
 */
class PrivacyRequestResource extends Resource
{
    protected static ?string $model = PrivacyRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Réglages';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Demandes de droits';

    protected static ?string $modelLabel = 'demande de droits';

    protected static ?string $pluralModelLabel = 'demandes de droits';

    protected static ?string $slug = 'demandes-droits';

    public static function getNavigationBadge(): ?string
    {
        $count = PrivacyRequest::query()->whereIn('status', [PrivacyRequestStatus::Received, PrivacyRequestStatus::Verifying, PrivacyRequestStatus::InProgress])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Demandeur')
                ->columns(2)
                ->schema([
                    TextInput::make('requester_name')->label('Nom')->required()->maxLength(160),
                    TextInput::make('requester_channel')->label('Téléphone ou e-mail')->required()->maxLength(190),
                    Select::make('type')->label('Demande')->options(PrivacyRequestType::options())->required()
                        ->disabled(fn (?PrivacyRequest $record): bool => $record !== null),
                    Select::make('contact_id')->label('Contact concerné')
                        ->helperText('Recherche par nom, téléphone ou e-mail. Changer de contact après la vérification oblige à vérifier à nouveau l’identité.')
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search): array => self::searchContacts($search))
                        ->getOptionLabelUsing(fn ($value): ?string => ($contact = Contact::query()->find($value)) !== null ? self::contactLabel($contact) : null)
                        ->disabled(fn (?PrivacyRequest $record): bool => $record?->contact?->anonymized_at !== null),
                    Textarea::make('details')->label('Précisions du demandeur')->rows(3)->maxLength(2000)->columnSpanFull(),
                ]),
            Section::make('Traitement')
                ->schema([
                    Textarea::make('decision_note')->label('Note de traitement')->rows(3)->maxLength(2000)
                        ->helperText('Ce qui a été vérifié et fait, sans recopier de pièce d’identité.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $tz = config('kh.display_timezone');

        return $table
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Aucune demande de droits')
            ->emptyStateDescription('Les demandes du formulaire « Gérer mes données » apparaissent ici.')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('contact'))
            ->columns([
                TextColumn::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (PrivacyRequestStatus $state): string => $state->label())
                    ->color(fn (PrivacyRequestStatus $state): string => match ($state) {
                        PrivacyRequestStatus::Received, PrivacyRequestStatus::Verifying => 'warning',
                        PrivacyRequestStatus::InProgress => 'info',
                        PrivacyRequestStatus::Done => 'success',
                        PrivacyRequestStatus::Rejected => 'gray',
                    }),
                TextColumn::make('type')->label('Demande')->formatStateUsing(fn (PrivacyRequestType $state): string => $state->label()),
                TextColumn::make('requester_name')->label('Demandeur')->searchable(),
                TextColumn::make('contact.full_name')->label('Contact relié')->placeholder('À relier'),
                TextColumn::make('verified_at')->label('Identité vérifiée')->dateTime('d/m/Y', timezone: $tz)->placeholder('Non'),
                TextColumn::make('created_at')->label('Reçue le')->dateTime('d/m/Y H:i', timezone: $tz)->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(PrivacyRequestStatus::options()),
                SelectFilter::make('type')->label('Demande')->options(PrivacyRequestType::options()),
            ]);
    }

    /**
     * @return array<int, string>
     */
    public static function searchContacts(string $search): array
    {
        $digits = (string) preg_replace('/\D+/', '', $search);

        return Contact::query()
            ->whereNull('anonymized_at')
            ->where(function (Builder $query) use ($search, $digits): void {
                $query->where('full_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                if (strlen($digits) >= 4) {
                    $query->orWhere('phone_e164', 'like', "%{$digits}%");
                }
            })
            ->limit(20)
            ->get()
            ->mapWithKeys(fn (Contact $contact): array => [$contact->id => self::contactLabel($contact)])
            ->all();
    }

    public static function contactLabel(Contact $contact): string
    {
        return trim($contact->full_name.' — '.implode(' · ', array_filter([$contact->phone_e164, $contact->email])));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrivacyRequests::route('/'),
            'create' => CreatePrivacyRequest::route('/create'),
            'edit' => EditPrivacyRequest::route('/{record}/edit'),
        ];
    }
}
