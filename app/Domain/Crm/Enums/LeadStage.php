<?php

namespace App\Domain\Crm\Enums;

enum LeadStage: string
{
    case New = 'new';
    case Qualified = 'qualified';
    case Meeting = 'meeting';
    case Proposal = 'proposal';
    case Won = 'won';
    case Lost = 'lost';
    case OnHold = 'on_hold';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouveau',
            self::Qualified => 'Qualifié',
            self::Meeting => 'Rendez-vous',
            self::Proposal => 'Proposition',
            self::Won => 'Gagné',
            self::Lost => 'Perdu',
            self::OnHold => 'En pause',
        };
    }

    /**
     * Transitions autorisées (CRM-02). « Gagné » est définitif ; un lead perdu ou en pause peut être repris.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Qualified, self::Meeting, self::Lost, self::OnHold],
            self::Qualified => [self::Meeting, self::Proposal, self::Lost, self::OnHold],
            self::Meeting => [self::Qualified, self::Proposal, self::Lost, self::OnHold],
            self::Proposal => [self::Meeting, self::Won, self::Lost, self::OnHold],
            self::OnHold => [self::New, self::Qualified, self::Meeting, self::Proposal, self::Lost],
            self::Lost => [self::Qualified],
            self::Won => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * Un motif est obligatoire pour perdre ou mettre en pause un lead.
     */
    public function requiresReason(): bool
    {
        return in_array($this, [self::Lost, self::OnHold], true);
    }

    /**
     * Étapes « vivantes » : un lead y attend une action de l'équipe.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::New, self::Qualified, self::Meeting, self::Proposal], true);
    }

    /**
     * @return list<self>
     */
    public static function active(): array
    {
        return array_values(array_filter(self::cases(), fn (self $stage): bool => $stage->isActive()));
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Qualified => 'primary',
            self::Meeting => 'warning',
            self::Proposal => 'primary',
            self::Won => 'success',
            self::Lost => 'danger',
            self::OnHold => 'gray',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }
}
