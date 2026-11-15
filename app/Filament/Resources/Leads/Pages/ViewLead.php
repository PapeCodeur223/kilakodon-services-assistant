<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadActions;
use App\Filament\Resources\Leads\LeadResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    public function getTitle(): string
    {
        return 'Lead #'.$this->getRecord()->getKey();
    }

    protected function getHeaderActions(): array
    {
        return [
            LeadActions::whatsapp(),
            LeadActions::logActivity(),
            LeadActions::assign(),
            EditAction::make()->label('Qualifier'),
            LeadActions::changeStage(),
        ];
    }
}
