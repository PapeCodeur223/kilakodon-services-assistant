<?php

namespace App\Filament\Resources\KnowledgeDocuments\Pages;

use App\Domain\Assistant\Actions\SaveManualKnowledgeDocument;
use App\Domain\Assistant\Models\KnowledgeDocument;
use App\Filament\Resources\KnowledgeDocuments\KnowledgeDocumentResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditKnowledgeDocument extends EditRecord
{
    protected static string $resource = KnowledgeDocumentResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var KnowledgeDocument $record */
        /** @var array{title: string, body: string, url?: string|null, review_at?: mixed, expires_at?: mixed} $data */
        return app(SaveManualKnowledgeDocument::class)->handle($data, $record);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
