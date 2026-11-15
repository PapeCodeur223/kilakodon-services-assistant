<?php

namespace App\Filament\Resources\Leads;

use App\Domain\Crm\Actions\AssignLead;
use App\Domain\Crm\Actions\ChangeLeadStage;
use App\Domain\Crm\Actions\LogActivity;
use App\Domain\Crm\Enums\ActivityType;
use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Crm\Enums\LostReason;
use App\Domain\Crm\Exceptions\CrmException;
use App\Domain\Crm\Models\Lead;
use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

/**
 * Actions d'un lead, partagées entre la liste, la fiche et le Kanban. Toutes passent par les Actions métier.
 */
final class LeadActions
{
    public static function changeStage(): Action
    {
        return Action::make('changeStage')
            ->label('Changer d\'étape')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->visible(fn (Lead $record): bool => self::user()?->can('changeStage', $record) === true)
            ->modalHeading('Changer d\'étape')
            ->schema([
                Select::make('stage')
                    ->label('Nouvelle étape')
                    ->options(fn (Lead $record): array => collect($record->stage->allowedTransitions())->mapWithKeys(fn (LeadStage $s): array => [$s->value => $s->label()])->all())
                    ->required()
                    ->live(),
                Select::make('lost_reason')
                    ->label('Motif de perte')
                    ->options(LostReason::options())
                    ->visible(fn (Get $get): bool => $get('stage') === LeadStage::Lost->value)
                    ->required(fn (Get $get): bool => $get('stage') === LeadStage::Lost->value),
                Textarea::make('reason')
                    ->label('Motif')
                    ->rows(2)
                    ->maxLength(255)
                    ->required(fn (Get $get): bool => $get('stage') === LeadStage::OnHold->value || $get('lost_reason') === LostReason::Other->value)
                    ->helperText('Obligatoire pour une mise en pause ou un motif « Autre ».'),
            ])
            ->action(function (Lead $record, array $data): void {
                self::run(fn () => app(ChangeLeadStage::class)->handle(
                    $record,
                    LeadStage::from((string) $data['stage']),
                    self::user(),
                    isset($data['reason']) ? (string) $data['reason'] : null,
                    isset($data['lost_reason']) ? LostReason::from((string) $data['lost_reason']) : null,
                ), 'Étape mise à jour');
            });
    }

    public static function assign(): Action
    {
        return Action::make('assign')
            ->label('Attribuer')
            ->icon(Heroicon::OutlinedUserCircle)
            ->color('gray')
            ->visible(fn (Lead $record): bool => self::user()?->can('assign', $record) === true)
            ->fillForm(fn (Lead $record): array => ['owner_id' => $record->owner_id])
            ->schema([
                Select::make('owner_id')
                    ->label('Responsable')
                    ->placeholder('Personne')
                    ->options(fn (): array => User::query()->where('is_active', true)->whereIn('role', [UserRole::Admin, UserRole::Sales])->orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->action(function (Lead $record, array $data): void {
                $owner = filled($data['owner_id'] ?? null) ? User::query()->find($data['owner_id']) : null;
                self::run(fn () => app(AssignLead::class)->handle($record, $owner, self::user()), 'Responsable mis à jour');
            });
    }

    /**
     * @param  (\Closure(): Lead)|null  $lead  lead cible quand l'action n'est pas posée sur une ligne (ex. en-tête des activités)
     */
    public static function logActivity(?\Closure $lead = null): Action
    {
        $resolve = fn (?Lead $record): ?Lead => $lead !== null ? $lead() : $record;

        return Action::make('logActivity')
            ->label('Ajouter une activité')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('gray')
            ->visible(fn (?Lead $record): bool => ($target = $resolve($record)) !== null && self::user()?->can('logActivity', $target) === true)
            ->schema([
                Select::make('type')
                    ->label('Type')
                    ->options(collect(ActivityType::cases())->reject(fn (ActivityType $t): bool => $t === ActivityType::System)->mapWithKeys(fn (ActivityType $t): array => [$t->value => $t->label()])->all())
                    ->default(ActivityType::Note->value)
                    ->required()
                    ->live(),
                Textarea::make('body')->label('Contenu')->rows(4)->required()->maxLength(LogActivity::BODY_MAX)
                    ->helperText('Interne : jamais visible du prospect.'),
                DateTimePicker::make('due_at')->label('Échéance')->seconds(false)->timezone(config('kh.display_timezone'))
                    ->visible(fn (Get $get): bool => $get('type') === ActivityType::Task->value)
                    ->required(fn (Get $get): bool => $get('type') === ActivityType::Task->value),
            ])
            ->action(function (?Lead $record, array $data) use ($resolve): void {
                self::run(fn () => app(LogActivity::class)->handle(
                    $resolve($record) ?? throw new CrmException('Lead introuvable.'),
                    ActivityType::from((string) $data['type']),
                    (string) $data['body'],
                    self::user(),
                    filled($data['due_at'] ?? null) ? Carbon::parse((string) $data['due_at']) : null,
                ), 'Activité enregistrée');
            });
    }

    public static function whatsapp(): Action
    {
        return Action::make('whatsapp')
            ->label('WhatsApp')
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->color('success')
            ->visible(fn (Lead $record): bool => filled($record->contact->whatsapp_e164 ?? $record->contact->phone_e164))
            ->url(fn (Lead $record): string => 'https://wa.me/'.ltrim((string) ($record->contact->whatsapp_e164 ?? $record->contact->phone_e164), '+'), shouldOpenInNewTab: true);
    }

    /**
     * Exécute une action métier et traduit ses refus en notification lisible.
     */
    public static function run(callable $callback, string $success): bool
    {
        try {
            $callback();
            Notification::make()->success()->title($success)->send();

            return true;
        } catch (CrmException $e) {
            Notification::make()->danger()->title('Action impossible')->body($e->getMessage())->send();

            return false;
        }
    }

    public static function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
