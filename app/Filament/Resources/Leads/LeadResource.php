<?php

namespace App\Filament\Resources\Leads;

use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Crm\Enums\LeadType;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Support\DuplicateFinder;
use App\Domain\Diagnostic\Engine\Catalog;
use App\Filament\Resources\Briefs\BriefResource;
use App\Filament\Resources\Contacts\ContactResource;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\LeadsKanban;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Filament\Resources\Leads\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\Leads\RelationManagers\AppointmentsRelationManager;
use App\Filament\Resources\Leads\RelationManagers\StageHistoryRelationManager;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Leads (CRM-02, CRM-03, CRM-08) : liste, Kanban, fiche complète et qualification.
 * L'étape ne se modifie jamais dans le formulaire : uniquement via « Changer d'étape » (historique, motif).
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

    public static function getNavigationBadge(): ?string
    {
        $count = Lead::query()->toHandle()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Leads à traiter';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['contact.company', 'owner', 'brief.latestVersion']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Section::make('Prochaine action')->columnSpanFull()->columns(2)->schema([
                TextInput::make('next_action')->label('Prochaine action')->maxLength(255)->placeholder('Ex. : rappeler pour fixer la démo'),
                DateTimePicker::make('next_action_at')->label('Pour le')->seconds(false)->timezone(config('kh.display_timezone')),
            ]),
            Section::make('Qualification')->description('Aide à la décision : aucune décision automatique de rejet (CRM-03).')->columnSpanFull()->columns(3)->schema([
                Select::make('fit_score')->label('Adéquation')->options(QualificationOptions::fit()),
                Select::make('budget_band')->label('Budget déclaré')->options(fn (): array => QualificationOptions::for('budget_band')),
                Select::make('timeline')->label('Échéance')->options(fn (): array => QualificationOptions::for('timeline')),
                Select::make('decision_role')->label('Rôle dans la décision')->options(fn (): array => QualificationOptions::for('decision_role')),
                TextInput::make('estimated_amount_xof')->label('Montant estimé')->integer()->minValue(0)->suffix('FCFA')
                    ->helperText('Saisi par le commercial, jamais montré au prospect.'),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        $tz = config('kh.display_timezone');

        return $schema->columns(3)->components([
            Section::make('Suivi')->columnSpan(2)->columns(3)->schema([
                TextEntry::make('stage')->label('Étape')->badge()
                    ->formatStateUsing(fn (LeadStage $state): string => $state->label())
                    ->color(fn (LeadStage $state): string => $state->color()),
                TextEntry::make('owner.name')->label('Responsable')->placeholder('Non attribué'),
                TextEntry::make('first_response_at')->label('Première réponse')->dateTime('d/m/Y H:i', timezone: $tz)->placeholder('Pas encore'),
                TextEntry::make('next_action')->label('Prochaine action')->placeholder('Aucune — à traiter')->columnSpan(2),
                TextEntry::make('next_action_at')->label('Pour le')->dateTime('d/m/Y H:i', timezone: $tz)->placeholder('—'),
                TextEntry::make('type')->label('Origine')->badge()->formatStateUsing(fn (LeadType $state): string => $state->label()),
                TextEntry::make('source')->label('Source'),
                TextEntry::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i', timezone: $tz),
                TextEntry::make('message')->label('Demande')->columnSpanFull()->prose()->placeholder('—'),
            ]),
            Section::make('Contact')->columnSpan(1)->schema([
                TextEntry::make('contact.full_name')->label('Nom')
                    ->url(fn (Lead $record): string => ContactResource::getUrl('view', ['record' => $record->contact_id])),
                TextEntry::make('contact.company.name')->label('Entreprise')->placeholder('—'),
                TextEntry::make('contact.phone_e164')->label('Téléphone / WhatsApp')->copyable(),
                TextEntry::make('contact.email')->label('E-mail')->placeholder('—')->copyable(),
                TextEntry::make('duplicates')->label('Doublons possibles')
                    ->getStateUsing(fn (Lead $record): string => ($n = app(DuplicateFinder::class)->for($record->contact)->count()) > 0
                        ? $n.' contact(s) au même numéro ou e-mail — à vérifier'
                        : 'Aucun')
                    ->color(fn (string $state): string => $state === 'Aucun' ? 'gray' : 'warning'),
            ]),
            Section::make('Qualification')->columnSpan(2)->columns(3)->schema([
                TextEntry::make('fit_score')->label('Adéquation')->formatStateUsing(fn (?int $state): ?string => QualificationOptions::fit()[$state] ?? null)->placeholder('À évaluer'),
                TextEntry::make('maturity_level')->label('Maturité (diagnostic)')
                    ->formatStateUsing(fn (?int $state): ?string => $state !== null ? Catalog::MATURITY_LEVELS[$state]['label'].' ('.$state.'/4)' : null)->placeholder('—'),
                TextEntry::make('budget_band')->label('Budget déclaré')->formatStateUsing(fn (?string $state): ?string => QualificationOptions::label('budget_band', $state))->placeholder('—'),
                TextEntry::make('timeline')->label('Échéance')->formatStateUsing(fn (?string $state): ?string => QualificationOptions::label('timeline', $state))->placeholder('—'),
                TextEntry::make('decision_role')->label('Décideur')->formatStateUsing(fn (?string $state): ?string => QualificationOptions::label('decision_role', $state))->placeholder('—'),
                TextEntry::make('estimated_amount_xof')->label('Montant estimé')->numeric(thousandsSeparator: ' ')->suffix(' FCFA')->placeholder('—'),
            ]),
            Section::make('Brief et diagnostic')->columnSpan(1)->visible(fn (Lead $record): bool => $record->brief !== null)->schema([
                TextEntry::make('brief.reference')->label('Brief')
                    ->url(fn (Lead $record): ?string => $record->brief !== null ? BriefResource::getUrl('view', ['record' => $record->brief]) : null),
                TextEntry::make('brief.status')->label('Statut')->badge()->formatStateUsing(fn ($state): string => $state->label()),
                TextEntry::make('modules')->label('Modules recommandés')
                    ->getStateUsing(fn (Lead $record): string => collect((array) ($record->brief?->latestVersion->recommended['recommendations'] ?? []))->pluck('label')->implode(' · ') ?: 'Échange à prévoir'),
            ]),
            Section::make('Réponses du diagnostic')->columnSpanFull()->collapsible()->collapsed()
                ->visible(fn (Lead $record): bool => $record->brief !== null)
                ->schema([
                    TextEntry::make('answers')->hiddenLabel()->listWithLineBreaks()
                        ->getStateUsing(fn (Lead $record): array => collect((array) ($record->brief?->latestVersion->declared['answers'] ?? []))
                            ->map(fn (array $a): string => $a['question'].' — '.$a['answer'])->values()->all()),
                ]),
            Section::make('Conversation avec l\'assistant')->columnSpanFull()->collapsible()->collapsed()->schema([
                TextEntry::make('conversation')->hiddenLabel()->getStateUsing(fn (): string => 'Aucune conversation liée. L\'assistant IA arrive au lot 4.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $tz = config('kh.display_timezone');

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i', timezone: $tz)->sortable(),
                TextColumn::make('contact.full_name')->label('Contact')->searchable()
                    ->description(fn (Lead $record): ?string => $record->contact->company?->name),
                TextColumn::make('type')->label('Origine')->badge()->formatStateUsing(fn (LeadType $state): string => $state->label())->color('gray'),
                TextColumn::make('stage')->label('Étape')->badge()
                    ->formatStateUsing(fn (LeadStage $state): string => $state->label())
                    ->color(fn (LeadStage $state): string => $state->color()),
                TextColumn::make('next_action')->label('Prochaine action')->placeholder('À traiter')->limit(40)
                    ->description(fn (Lead $record): ?string => $record->next_action_at?->timezone($tz)->format('d/m H:i'))
                    ->color(fn (Lead $record): ?string => $record->next_action === null || ($record->next_action_at?->isPast() ?? true) ? 'danger' : null),
                TextColumn::make('owner.name')->label('Responsable')->placeholder('—'),
                TextColumn::make('source')->label('Source')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('to_handle')->label('À traiter')->query(fn (Builder $query): Builder => (new Lead)->scopeToHandle($query))->default(false),
                Filter::make('mine')->label('Mes leads')->query(fn (Builder $query): Builder => $query->where('owner_id', auth()->id())),
                SelectFilter::make('stage')->label('Étape')->options(LeadStage::options()),
                SelectFilter::make('type')->label('Origine')->options(LeadType::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make([
                    LeadActions::changeStage(),
                    LeadActions::assign(),
                    LeadActions::logActivity(),
                    EditAction::make()->label('Qualifier'),
                    LeadActions::whatsapp(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ActivitiesRelationManager::class,
            AppointmentsRelationManager::class,
            StageHistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeads::route('/'),
            'kanban' => LeadsKanban::route('/kanban'),
            'view' => ViewLead::route('/{record}'),
            'edit' => EditLead::route('/{record}/edit'),
        ];
    }
}
