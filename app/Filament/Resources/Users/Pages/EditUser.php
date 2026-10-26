<?php

namespace App\Filament\Resources\Users\Pages;

use App\Domain\Platform\Models\User;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Contrôle serveur, indépendant de l'état « désactivé » des champs :
     * personne ne modifie son propre rôle ni ne désactive son propre compte.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $actor = auth()->user();
        $record = $this->getRecord();

        if (! $actor instanceof User || ! $record instanceof User || $actor->cannot('changeRole', $record)) {
            unset($data['role'], $data['is_active']);
        }

        return $data;
    }
}
