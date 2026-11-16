<?php

namespace App\Policies;

use App\Domain\Assistant\Enums\KnowledgeSourceType;
use App\Domain\Assistant\Enums\KnowledgeStatus;
use App\Domain\Assistant\Models\KnowledgeDocument;
use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\User;

/**
 * Base de connaissances (PRD §3) : l'éditeur prépare des brouillons, seul l'admin approuve ou retire.
 * Les documents issus des contenus se modifient via le contenu source (reconstruction automatique).
 */
class KnowledgeDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Editor);
    }

    public function view(User $user, KnowledgeDocument $document): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, KnowledgeDocument $document): bool
    {
        return $this->viewAny($user) && $document->source_type === KnowledgeSourceType::Manual;
    }

    public function approve(User $user, KnowledgeDocument $document): bool
    {
        return $user->isAdmin() && $document->status !== KnowledgeStatus::Approved && $document->chunks()->exists();
    }

    public function retire(User $user, KnowledgeDocument $document): bool
    {
        return $user->isAdmin() && $document->status !== KnowledgeStatus::Retired;
    }

    public function delete(User $user, KnowledgeDocument $document): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
