<?php

namespace App\Domain\Content\Contracts;

use App\Domain\Content\Enums\ContentStatus;
use Illuminate\Support\Carbon;

/**
 * Contenu publiable et versionné (traits Publishable + HasRevisions).
 *
 * @property ContentStatus $status
 * @property Carbon|null $published_at
 */
interface PublishableContent
{
    /**
     * @return list<string>
     */
    public function contentFields(): array;

    /**
     * @return list<string>
     */
    public function revisionFields(): array;

    /**
     * @return list<string>
     */
    public function pendingValidationFields(): array;

    public function publicUrl(): string;

    public function isPublished(): bool;
}
