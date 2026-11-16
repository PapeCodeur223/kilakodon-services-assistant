<?php

namespace App\Domain\Assistant\Actions;

use App\Domain\Assistant\Enums\ProposalStatus;
use App\Domain\Assistant\Models\AiActionProposal;

/**
 * Le visiteur refuse la proposition : rien n'est écrit. Sans effet sur une proposition déjà traitée.
 */
class CancelAssistantAction
{
    public function handle(AiActionProposal $proposal): AiActionProposal
    {
        // Mise à jour conditionnelle : une proposition exécutée entre-temps n'est jamais réécrite.
        AiActionProposal::query()
            ->whereKey($proposal->getKey())
            ->where('status', ProposalStatus::Proposed)
            ->update(['status' => ProposalStatus::Cancelled, 'updated_at' => now()]);

        return $proposal->refresh();
    }
}
