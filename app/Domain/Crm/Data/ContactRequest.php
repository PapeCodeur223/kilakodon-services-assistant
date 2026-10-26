<?php

namespace App\Domain\Crm\Data;

/**
 * Demande issue du formulaire de contact, déjà validée par la Form Request.
 */
final readonly class ContactRequest
{
    /**
     * @param  array<string, string>  $utm
     */
    public function __construct(
        public string $fullName,
        public string $phone,
        public ?string $email,
        public string $subject,
        public string $message,
        public bool $marketingOptIn,
        public ?string $ip = null,
        public array $utm = [],
        public ?string $company = null,
    ) {}
}
