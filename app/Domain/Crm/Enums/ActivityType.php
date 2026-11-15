<?php

namespace App\Domain\Crm\Enums;

enum ActivityType: string
{
    case Note = 'note';
    case Call = 'call';
    case Whatsapp = 'whatsapp';
    case Email = 'email';
    case Meeting = 'meeting';
    case Task = 'task';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Note => 'Note',
            self::Call => 'Appel',
            self::Whatsapp => 'WhatsApp',
            self::Email => 'E-mail',
            self::Meeting => 'Rendez-vous',
            self::Task => 'Tâche',
            self::System => 'Système',
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
