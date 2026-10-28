<?php

namespace App\Filament\Resources\DiagnosticTemplates\Pages;

use App\Filament\Resources\DiagnosticTemplates\DiagnosticTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDiagnosticTemplates extends ListRecords
{
    protected static string $resource = DiagnosticTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
