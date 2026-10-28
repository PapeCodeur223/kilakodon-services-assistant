<?php

namespace Database\Seeders;

use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Crm\Enums\LeadType;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Lead;
use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\User;
use Illuminate\Database\Seeder;

/**
 * Données de développement — exécuté uniquement en environnement local.
 * Comptes et leads FICTIFS (domaine réservé .test, numéros 7000xxxx) : jamais en production.
 */
class DevSeeder extends Seeder
{
    public const PASSWORD = 'kilakodon-dev-2026';

    public function run(): void
    {
        $users = [
            ['name' => 'Admin Démo', 'email' => 'admin@kilakodon.test', 'role' => UserRole::Admin],
            ['name' => 'Commercial Démo', 'email' => 'sales@kilakodon.test', 'role' => UserRole::Sales],
            ['name' => 'Éditeur Démo', 'email' => 'editor@kilakodon.test', 'role' => UserRole::Editor],
        ];

        foreach ($users as $user) {
            User::query()->firstOrCreate(
                ['email' => $user['email']],
                ['name' => $user['name'], 'role' => $user['role'], 'password' => self::PASSWORD, 'is_active' => true],
            );
        }

        $this->restoreAdminMultiFactor();
        $this->seedLeads(User::query()->where('email', 'sales@kilakodon.test')->value('id'));
    }

    /**
     * Après un `migrate:fresh`, l'admin de développement retrouve le secret MFA déjà enregistré dans
     * l'application d'authentification (KH_DEV_ADMIN_MFA_SECRET, .env local uniquement) : pas de
     * nouvelle configuration à chaque réinitialisation de la base.
     */
    private function restoreAdminMultiFactor(): void
    {
        $secret = config('kh.dev_admin_mfa_secret');
        $admin = User::query()->where('email', 'admin@kilakodon.test')->first();

        if (is_string($secret) && $secret !== '' && $admin !== null && $admin->getAppAuthenticationSecret() === null) {
            $admin->saveAppAuthenticationSecret($secret);
        }
    }

    /**
     * 15 leads fictifs répartis dans les étapes (BACKEND_SCHEMA §9).
     */
    private function seedLeads(?int $ownerId): void
    {
        if (Lead::query()->exists()) {
            return;
        }

        $stages = [
            LeadStage::New, LeadStage::New, LeadStage::New, LeadStage::New,
            LeadStage::Qualified, LeadStage::Qualified, LeadStage::Qualified,
            LeadStage::Meeting, LeadStage::Meeting,
            LeadStage::Proposal, LeadStage::Proposal,
            LeadStage::Won, LeadStage::Lost, LeadStage::OnHold, LeadStage::New,
        ];
        $types = [LeadType::Contact, LeadType::Diagnostic, LeadType::Lab, LeadType::Assistant];

        foreach ($stages as $i => $stage) {
            $n = $i + 1;
            $contact = Contact::query()->create([
                'full_name' => "Prospect fictif {$n}",
                'phone_e164' => sprintf('+2237000%04d', $n),
                'whatsapp_e164' => sprintf('+2237000%04d', $n),
                'email' => "prospect{$n}@example.test",
            ]);

            $lead = new Lead([
                'contact_id' => $contact->id,
                'type' => $types[$i % count($types)],
                'source' => 'dev-seed',
                'message' => 'Lead fictif de développement.',
                'owner_id' => $stage === LeadStage::New ? null : $ownerId,
                'idempotency_key' => hash('sha256', 'dev-seed-'.$n),
            ]);
            $lead->stage = $stage;
            $lead->save();
        }
    }
}
