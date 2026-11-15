<?php

namespace App\Domain\Crm\Enums;

enum PrivacyRequestStatus: string
{
    case Received = 'received';
    case Verifying = 'verifying';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Reçue',
            self::Verifying => 'Vérification',
            self::InProgress => 'En cours',
            self::Done => 'Traitée',
            self::Rejected => 'Rejetée',
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
