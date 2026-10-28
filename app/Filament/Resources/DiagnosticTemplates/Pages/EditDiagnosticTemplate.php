<?php

namespace App\Filament\Resources\DiagnosticTemplates\Pages;

use App\Filament\Resources\DiagnosticTemplates\DiagnosticTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDiagnosticTemplate extends EditRecord
{
    protected static string $resource = DiagnosticTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [...DiagnosticTemplateResource::lifecycleActions(), DeleteAction::make()];
    }
}
