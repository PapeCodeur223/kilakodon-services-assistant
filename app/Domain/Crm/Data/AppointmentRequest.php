<?php

namespace App\Domain\Crm\Data;

use App\Domain\Brief\Models\Brief;
use App\Domain\Crm\Enums\AppointmentChannel;

/**
 * Demande de rendez-vous du formulaire public (RDV-01), déjà validée.
 * `brief` : brief accessible d'où vient le visiteur (lien privé), pour rattacher la demande à son lead.
 */
final readonly class AppointmentRequest
{
    /**
     * @param  list<array{date: string, period: string}>  $slots  1 à 3 créneaux souhaités (date AAAA-MM-JJ, Bamako)
     * @param  array<string, string>  $utm
     */
    public function __construct(
        public string $fullName,
        public string $phone,
        public ?string $email,
        public ?string $company,
        public string $topic,
        public AppointmentChannel $channel,
        public array $slots,
        public bool $marketingOptIn = false,
        public ?string $ip = null,
        public array $utm = [],
        public ?Brief $brief = null,
    ) {}
}
