<?php

namespace App\Filament\Pages;

use App\Domain\Platform\Actions\UpdateSettings;
use App\Domain\Platform\Models\User;
use App\Domain\Platform\Services\Settings as SettingsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * Paramètres de la plateforme (ADM-01) — administrateurs uniquement.
 * Les secrets (clé API, SMTP) restent dans .env et n'apparaissent jamais ici.
 *
 * @property-read Schema $form
 */
class Settings extends Page
{
    /**
     * Correspondance champ du formulaire → clé de paramètre.
     */
    private const FIELDS = [
        'whatsapp_primary' => 'contact.whatsapp_primary',
        'whatsapp_secondary' => 'contact.whatsapp_secondary',
        'email' => 'contact.email',
        'city' => 'contact.city',
        'hours' => 'contact.hours',
        'lead_recipients' => 'notify.lead_recipients',
        'ai_enabled' => 'ai.enabled',
        'ai_daily_budget_usd' => 'ai.daily_budget_usd',
        'ai_max_messages_per_conversation' => 'ai.max_messages_per_conversation',
    ];

    private const PHONE_RULE = 'regex:/^\+[1-9]\d{7,14}$/';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Réglages';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Paramètres';

    protected static ?string $title = 'Paramètres';

    protected static ?string $slug = 'parametres';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Gate::allows('manage-settings');
    }

    public function mount(): void
    {
        $settings = app(SettingsService::class);
        $state = [];

        foreach (self::FIELDS as $field => $key) {
            $state[$field] = $settings->get($key);
        }

        $consent = (array) $settings->get('consent.texts', []);
        $state['consent_version'] = $consent['version'] ?? 1;
        $state['consent_processing'] = $consent['processing'] ?? null;
        $state['consent_marketing'] = $consent['marketing'] ?? null;

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Coordonnées')
                    ->description('Affichées dans le pied de page, la page contact et les e-mails.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('whatsapp_primary')
                            ->label('WhatsApp principal')
                            ->tel()
                            ->required()
                            ->rule(self::PHONE_RULE)
                            ->helperText('Format international, ex. +22374666489'),
                        TextInput::make('whatsapp_secondary')
                            ->label('Téléphone secondaire')
                            ->tel()
                            ->nullable()
                            ->rule(self::PHONE_RULE),
                        TextInput::make('email')
                            ->label('E-mail de contact')
                            ->email()
                            ->required()
                            ->maxLength(190),
                        TextInput::make('city')
                            ->label('Ville')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('hours')
                            ->label('Horaires de réponse')
                            ->required()
                            ->maxLength(120)
                            ->columnSpanFull(),
                    ]),
                Section::make('Notifications')
                    ->schema([
                        TagsInput::make('lead_recipients')
                            ->label('Destinataires des nouveaux leads')
                            ->placeholder('Ajouter une adresse e-mail')
                            ->required()
                            ->nestedRecursiveRules(['email:rfc']),
                    ]),
                Section::make('Assistant IA')
                    ->description('La clé API reste dans le fichier .env, jamais en base.')
                    ->columns(3)
                    ->schema([
                        Toggle::make('ai_enabled')
                            ->label('Assistant activé'),
                        TextInput::make('ai_daily_budget_usd')
                            ->label('Budget quotidien (USD)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1000)
                            ->required(),
                        TextInput::make('ai_max_messages_per_conversation')
                            ->label('Messages max. par conversation')
                            ->integer()
                            ->minValue(1)
                            ->maxValue(100)
                            ->required(),
                    ]),
                Section::make('Textes de consentement')
                    ->description('Toute modification crée une nouvelle version ; chaque consentement enregistré garde la sienne.')
                    ->schema([
                        TextInput::make('consent_version')
                            ->label('Version en vigueur')
                            ->disabled()
                            ->dehydrated(false),
                        Textarea::make('consent_processing')
                            ->label('Traitement de la demande (obligatoire)')
                            ->required()
                            ->rows(2)
                            ->maxLength(500),
                        Textarea::make('consent_marketing')
                            ->label('Actualités (facultatif, jamais pré-coché)')
                            ->required()
                            ->rows(2)
                            ->maxLength(500),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Enregistrer')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $state = $this->form->getState();
        $values = [];

        foreach (self::FIELDS as $field => $key) {
            $values[$key] = $this->normalize($key, $state[$field] ?? null);
        }

        $values['consent.texts'] = [
            'processing' => trim((string) $state['consent_processing']),
            'marketing' => trim((string) $state['consent_marketing']),
        ];

        $changed = app(UpdateSettings::class)->handle($values, $user);

        $this->mount();

        Notification::make()
            ->success()
            ->title($changed === [] ? 'Aucune modification' : 'Paramètres enregistrés')
            ->send();
    }

    private function normalize(string $key, mixed $value): mixed
    {
        return match ($key) {
            'ai.enabled' => (bool) $value,
            'ai.daily_budget_usd' => $this->money((float) $value),
            'ai.max_messages_per_conversation' => (int) $value,
            'notify.lead_recipients' => array_values(array_unique(array_map('trim', (array) $value))),
            'contact.whatsapp_secondary' => filled($value) ? trim((string) $value) : null,
            default => is_string($value) ? trim($value) : $value,
        };
    }

    /**
     * Montant à 2 décimales ; entier quand c'est possible, pour comparer sans faux changement.
     */
    private function money(float $value): int|float
    {
        $rounded = round($value, 2);

        return floor($rounded) === $rounded ? (int) $rounded : $rounded;
    }
}
