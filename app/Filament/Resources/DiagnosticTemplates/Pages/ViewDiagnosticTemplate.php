<?php

namespace App\Filament\Resources\DiagnosticTemplates\Pages;

use App\Filament\Resources\DiagnosticTemplates\DiagnosticTemplateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDiagnosticTemplate extends ViewRecord
{
    protected static string $resource = DiagnosticTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [...DiagnosticTemplateResource::lifecycleActions(), EditAction::make()];
    }
}
