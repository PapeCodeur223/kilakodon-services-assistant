<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\PrivacyRequestStatus;
use App\Domain\Crm\Enums\PrivacyRequestType;
use App\Domain\Crm\Models\PrivacyRequest;
use App\Domain\Platform\Actions\QueueOutboundMessage;
use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\User;
use App\Domain\Platform\Support\IpHasher;

/**
 * Demande de droits reçue par le formulaire public (PRV-02). Aucun traitement automatique :
 * la demande est enregistrée « reçue », l'équipe vérifie l'identité puis traite dans l'admin.
 * Les administrateurs sont prévenus par e-mail, sans recopier les coordonnées.
 */
class SubmitPrivacyRequest
{
    public function __construct(
        private readonly QueueOutboundMessage $queue,
        private readonly IpHasher $ipHasher,
    ) {}

    public function handle(string $name, string $channel, PrivacyRequestType $type, ?string $details, ?string $ip): PrivacyRequest
    {
        $request = PrivacyRequest::query()->create([
            'requester_name' => mb_substr(trim($name), 0, 160),
            'requester_channel' => mb_substr(trim($channel), 0, 190),
            'type' => $type,
            'details' => $details !== null && trim($details) !== '' ? mb_substr(trim($details), 0, 2000) : null,
            'ip_hash' => $ip !== null ? $this->ipHasher->hash($ip) : null,
            'status' => PrivacyRequestStatus::Received,
        ]);

        $admins = User::query()->where('role', UserRole::Admin)->where('is_active', true)->pluck('email');
        foreach ($admins as $email) {
            $this->queue->handle((string) $email, 'privacy_request_internal', [
                'privacy_request_id' => $request->id,
                'type' => $type->value,
            ], $request);
        }

        return $request;
    }
}
