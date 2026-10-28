<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Qualification et prochaine action. L'étape et le responsable ont leurs propres actions.
 */
class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    public function getTitle(): string
    {
        return 'Qualifier le lead #'.$this->getRecord()->getKey();
    }

    protected function getRedirectUrl(): string
    {
        return LeadResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
