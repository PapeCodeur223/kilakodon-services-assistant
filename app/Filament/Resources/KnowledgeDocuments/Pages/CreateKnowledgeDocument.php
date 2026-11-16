<?php

namespace App\Filament\Resources\KnowledgeDocuments\Pages;

use App\Domain\Assistant\Actions\SaveManualKnowledgeDocument;
use App\Filament\Resources\KnowledgeDocuments\KnowledgeDocumentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateKnowledgeDocument extends CreateRecord
{
    protected static string $resource = KnowledgeDocumentResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var array{title: string, body: string, url?: string|null, review_at?: mixed, expires_at?: mixed} $data */
        return app(SaveManualKnowledgeDocument::class)->handle($data);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
