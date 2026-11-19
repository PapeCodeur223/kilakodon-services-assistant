<?php

namespace App\Filament\Resources\PrivacyRequests\Pages;

use App\Filament\Resources\PrivacyRequests\PrivacyRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePrivacyRequest extends CreateRecord
{
    protected static string $resource = PrivacyRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return PrivacyRequestResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
