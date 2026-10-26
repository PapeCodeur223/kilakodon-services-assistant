<?php

namespace App\Filament\Resources\CaseStudies\Pages;

use App\Filament\Resources\CaseStudies\CaseStudyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCaseStudy extends CreateRecord
{
    protected static string $resource = CaseStudyResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
