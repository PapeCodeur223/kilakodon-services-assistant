<?php

namespace App\Filament\Resources\DiagnosticTemplates\Pages;

use App\Domain\Diagnostic\Enums\TemplateStatus;
use App\Filament\Resources\DiagnosticTemplates\DiagnosticTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDiagnosticTemplate extends CreateRecord
{
    protected static string $resource = DiagnosticTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'status' => TemplateStatus::Draft];
    }
}
