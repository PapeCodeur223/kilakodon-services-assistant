<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Domain\Crm\Actions\ChangeLeadStage;
use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Crm\Enums\LostReason;
use App\Domain\Crm\Models\Lead;
use App\Filament\Resources\Leads\LeadActions;
use App\Filament\Resources\Leads\LeadResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

/**
 * Kanban des leads par étape. Glisser une carte appelle `ChangeLeadStage` ; « Perdu » et « En pause »
 * ouvrent une modale de motif. Les transitions interdites sont refusées par l'Action, jamais par l'écran seul.
 */
class LeadsKanban extends Page
{
    protected static string $resource = LeadResource::class;

    protected string $view = 'filament.leads.kanban';

    /** Cartes affichées par colonne ; « Gagné » et « Perdu » : 30 derniers jours. */
    public const PER_COLUMN = 50;

    public function getTitle(): string|Htmlable
    {
        return 'Pipeline des leads';
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->can('viewAny', Lead::class) === true;
    }

    /**
     * @return array<string, Collection<int, Lead>>
     */
    public function getColumns(): array
    {
        $columns = [];

        foreach (LeadStage::cases() as $stage) {
            $query = Lead::query()->with(['contact.company', 'owner'])->where('stage', $stage);

            if (! $stage->isActive() && $stage !== LeadStage::OnHold) {
                $query->where('updated_at', '>=', now()->subDays(30));
            }

            $columns[$stage->value] = $query->latest('updated_at')->limit(self::PER_COLUMN)->get();
        }

        return $columns;
    }

    /**
     * Dépôt d'une carte dans une colonne.
     */
    public function moveLead(int $leadId, string $stage): void
    {
        $lead = Lead::query()->find($leadId);
        $to = LeadStage::tryFrom($stage);

        if ($lead === null || $to === null || $lead->stage === $to) {
            return;
        }

        abort_unless(LeadActions::user()?->can('changeStage', $lead) === true, 403);

        if ($to->requiresReason()) {
            $this->mountAction('moveWithReason', ['lead' => $lead->id, 'stage' => $to->value]);

            return;
        }

        LeadActions::run(fn () => app(ChangeLeadStage::class)->handle($lead, $to, LeadActions::user()), "Lead #{$lead->id} : {$to->label()}");
    }

    public function moveWithReasonAction(): Action
    {
        return Action::make('moveWithReason')
            ->modalHeading(fn (array $arguments): string => 'Passer en « '.(LeadStage::tryFrom((string) ($arguments['stage'] ?? ''))?->label() ?? '').' »')
            ->modalSubmitActionLabel('Confirmer')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->schema(fn (array $arguments): array => [
                Select::make('lost_reason')
                    ->label('Motif de perte')
                    ->options(LostReason::options())
                    ->visible(($arguments['stage'] ?? null) === LeadStage::Lost->value)
                    ->required(($arguments['stage'] ?? null) === LeadStage::Lost->value)
                    ->live(),
                Textarea::make('reason')
                    ->label('Motif')
                    ->rows(2)
                    ->maxLength(255)
                    ->required(fn (Get $get): bool => ($arguments['stage'] ?? null) === LeadStage::OnHold->value || $get('lost_reason') === LostReason::Other->value),
            ])
            ->action(function (array $data, array $arguments): void {
                $lead = Lead::query()->find((int) ($arguments['lead'] ?? 0));
                $to = LeadStage::tryFrom((string) ($arguments['stage'] ?? ''));

                if ($lead === null || $to === null) {
                    Notification::make()->danger()->title('Lead introuvable')->send();

                    return;
                }

                abort_unless(LeadActions::user()?->can('changeStage', $lead) === true, 403);

                LeadActions::run(fn () => app(ChangeLeadStage::class)->handle(
                    $lead,
                    $to,
                    LeadActions::user(),
                    isset($data['reason']) ? (string) $data['reason'] : null,
                    isset($data['lost_reason']) ? LostReason::from((string) $data['lost_reason']) : null,
                ), "Lead #{$lead->id} : {$to->label()}");
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('list')->label('Vue liste')->icon(Heroicon::OutlinedListBullet)->color('gray')->url(LeadResource::getUrl('index')),
        ];
    }
}
