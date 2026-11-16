<?php

namespace App\Domain\Assistant\Enums;

enum SuggestedAction: string
{
    case None = 'none';
    case StartDiagnostic = 'start_diagnostic';
    case ProposeBrief = 'propose_brief';
    case RequestAppointment = 'request_appointment';
    case HandoffWhatsapp = 'handoff_whatsapp';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Aucune',
            self::StartDiagnostic => 'Diagnostic',
            self::ProposeBrief => 'Brief',
            self::RequestAppointment => 'Rendez-vous',
            self::HandoffWhatsapp => 'WhatsApp',
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
